<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace theme_epure;

/**
 * Tests of the reading of the SCORM packages.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \theme_epure\scorm_check
 */
final class scorm_check_test extends \advanced_testcase {
    /**
     * Files of a package, stored as Moodle would have unzipped them.
     *
     * @param array $contents Content by path.
     * @return \stored_file[] By path.
     */
    protected function package(array $contents): array {
        static $item = 0;
        $item++;
        $fs = get_file_storage();
        $context = \context_system::instance();
        $files = [];
        foreach ($contents as $path => $content) {
            $dir = dirname($path);
            $files[$path] = $fs->create_file_from_string([
                'contextid' => $context->id,
                'component' => 'theme_epure',
                'filearea' => 'scormtest',
                'itemid' => $item,
                'filepath' => $dir === '.' ? '/' : '/' . $dir . '/',
                'filename' => basename($path),
            ], $content);
        }
        return $files;
    }

    /**
     * A file of data of Storyline: the JSON in a string of JavaScript between single quotes.
     *
     * @param string $kind slide or data.
     * @param array $data Data.
     * @return string
     */
    protected function storyline(string $kind, array $data): string {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $literal = str_replace(['\\', "'"], ['\\\\', "\\'"], $json);
        return "window.globalProvideData('$kind', '$literal');";
    }

    /**
     * An object of a slide of Storyline.
     *
     * @param string $type Type for the screen readers.
     * @param string $alt Text alternative.
     * @param int $state 0 when seen by the screen readers.
     * @return array
     */
    protected function shape(string $type, string $alt, int $state = 0): array {
        return ['kind' => 'vectorshape', 'accType' => $type, 'id' => uniqid(),
            'data' => ['accState' => $state, 'vectorData' => ['altText' => $alt]]];
    }

    /**
     * The names that say nothing: files, shapes, nothing.
     */
    public function test_meaningless(): void {
        $this->assertTrue(scorm_check::meaningless(''));
        $this->assertTrue(scorm_check::meaningless('bt_suivant.png'));
        $this->assertTrue(scorm_check::meaningless('Rectangle 12'));
        $this->assertTrue(scorm_check::meaningless('Groupe\r\n 1'));
        $this->assertFalse(scorm_check::meaningless('Next slide'));
        $this->assertFalse(scorm_check::meaningless('Plan d\'évacuation du bâtiment'));
    }

    /**
     * The data of Storyline is read whatever its quotes and escapes.
     */
    public function test_storyline_data(): void {
        $data = ['title' => 'L\'étape « 1 » \\ "fin"', 'list' => [1, 2]];
        $read = scorm_check::storyline_data($this->storyline('slide', $data));
        $this->assertSame('slide', $read['kind']);
        $this->assertSame($data, $read['data']);
        $this->assertNull(scorm_check::storyline_data('var x = 1;'));
    }

    /**
     * A package of Storyline: its images and buttons without a useful name once each, the states of a button as one,
     * those hidden from the screen readers left alone; its sounds by file, with or without captions.
     */
    public function test_storyline(): void {
        $this->resetAfterTest();
        $slide = ['title' => 'Intro', 'objects' => [
            $this->shape('image', 'bandeau.png'),
            $this->shape('image', 'bandeau.png'),
            $this->shape('button', 'bt_suivant.png'),
            $this->shape('image', 'bt_suivant_over.png'),
            $this->shape('button', ''),
            $this->shape('image', 'decor.png', 1),
            $this->shape('image', 'Fire exit plan'),
            $this->shape('text', 'Rectangle 1'),
            ['kind' => 'audio', 'assetId' => 1, 'id' => 'a1'],
            ['kind' => 'audio', 'assetId' => 2, 'id' => 'a2'],
            ['kind' => 'audio', 'assetId' => 3, 'id' => 'a3', 'closedCaptions' => [['text' => 'Hello']]],
        ]];
        $files = $this->package([
            'story.html' => '<html></html>',
            'html5/data/js/data.js' => $this->storyline('data', ['assetLib' => [
                ['id' => 1, 'url' => 'story_content/one.mp3'],
                ['id' => 2, 'url' => 'story_content/one.mp3'],
                ['id' => 3, 'url' => 'story_content/three.mp3'],
            ]]),
            'html5/data/js/abc.js' => $this->storyline('slide', $slide),
        ]);
        $package = scorm_check::read($files);
        $this->assertSame('storyline', $package['tool']);
        // Bandeau, the button bt_suivant (with its state hover), the button without a name.
        $this->assertSame(3, $package['issues']['scormimages']);
        // One sound file without captions, played by two assets; the other has captions.
        $this->assertSame(1, $package['issues']['scormaudio']);
    }

    /**
     * A course of Rise: its images without a text alternative (not those of decoration), its sounds and embedded
     * contents to confirm, and the Storyline blocks it holds.
     */
    public function test_rise(): void {
        $this->resetAfterTest();
        $image = fn(string $name, array $more = []): array => ['image' => ['key' => 'rise/' . $name,
            'originalUrl' => $name] + $more];
        $course = ['course' => [
            'coverImage' => ['media' => $image('cover.png')],
            'lessons' => [['items' => [
                ['type' => 'interactive', 'background' => ['media' => $image('fond.png')],
                    'items' => [['media' => $image('plan.png')], ['media' => ['audio' => ['key' => 'a.mp3',
                    'originalUrl' => 'Intro.mp3']]]]],
                ['type' => 'quote', 'items' => [['avatar' => ['media' => $image('anne.png')]]]],
                ['type' => 'image', 'items' => [['media' => $image('chart.png', ['altText' => 'Sales by year'])]]],
                ['type' => 'multimedia', 'items' => [['media' => ['embed' => [
                    'src' => '//cdn.embedly.com/widgets/media.html?url=https%3A%2F%2Fview.genially.com%2Fabc']]]]],
                ['type' => 'text', 'items' => [['paragraph' => '<p><a href="https://example.com">Cliquez ici</a></p>']]],
            ]]],
        ]];
        $files = $this->package([
            'scormcontent/index.html' => '<html></html>',
            'scormcontent/locales/en.js' => 'window.__resolveJsonp("course:en", "' . base64_encode(json_encode($course)) . '")',
            'scormcontent/assets/blk/html5/data/js/data.js' => $this->storyline('data', ['assetLib' => []]),
            'scormcontent/assets/blk/html5/data/js/s1.js' => $this->storyline('slide', ['objects' => [
                $this->shape('button', 'Rectangle'),
            ]]),
        ]);
        $package = scorm_check::read($files);
        $this->assertSame('rise', $package['tool']);
        // The plan without an alternative, and the button of the Storyline block.
        $this->assertSame(2, $package['issues']['scormimages']);
        $this->assertSame(1, $package['issues']['links']);
        $kinds = array_column($package['checks'], 'label', 'kind');
        $this->assertSame('Intro.mp3', $kinds['transcript']);
        $this->assertSame('view.genially.com', $kinds['embedded']);
    }

    /**
     * A package of HTML pages: read as a text of the course; the other tools named.
     */
    public function test_html_and_tools(): void {
        $this->resetAfterTest();
        $files = $this->package([
            'imsmanifest.xml' => '<manifest/>',
            'index.html' => '<html><body><h1>Safety</h1><img src="a.png"><p><a href="x.html">click here</a></p></body></html>',
        ]);
        $package = scorm_check::read($files);
        $this->assertSame('html', $package['tool']);
        $this->assertSame(1, $package['issues']['scormimages']);
        $this->assertSame(1, $package['issues']['links']);

        $this->assertSame('captivate', scorm_check::tool($this->package(['assets/js/CPXHRLoader.js' => '//'])));
        $this->assertSame('unknown', scorm_check::tool($this->package(['imsmanifest.xml' => '<manifest/>'])));
    }
}
