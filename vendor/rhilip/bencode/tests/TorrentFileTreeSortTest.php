<?php


use Rhilip\Bencode\TorrentFile;
use PHPUnit\Framework\TestCase;

class TorrentFileTreeSortTest extends TestCase
{

    protected function setUp(): void
    {
        $this->torrent = TorrentFile::load("tests/asserts/test-tree-sort.torrent");
        $this->torrent->parse();
    }

    public function testGetFileTreeByParse()
    {
        $this->assertEquals(
            json_encode([
                'test_tree_sort' => [
                    'file.txt' => 1048576,
                    'z' => [
                        'c.txt' => 1048576,
                    ],
                    'a' => [
                        'd.txt' => 1048576,
                        'c.txt' => 1048576
                    ]
                ]
            ]),
            json_encode($this->torrent->getFileTree())
        );
    }

    public function testGetFileTreeByString()
    {
        $this->assertEquals(
            json_encode([
                'test_tree_sort' => [
                    'a' => [
                        'c.txt' => 1048576,
                        'd.txt' => 1048576
                    ],
                    'file.txt' => 1048576,
                    'z' => [
                        'c.txt' => 1048576,
                    ],
                ]
            ]),
            json_encode($this->torrent->getFileTree(TorrentFile::FILETREE_SORT_STRING))
        );
    }

    public function testGetFileTreeByFolder()
    {
        $this->assertEquals(
            json_encode([
                'test_tree_sort' => [
                    'z' => [
                        'c.txt' => 1048576,
                    ],
                    'a' => [
                        'd.txt' => 1048576,
                        'c.txt' => 1048576
                    ],
                    'file.txt' => 1048576
                ]
            ]),
            json_encode($this->torrent->getFileTree(TorrentFile::FILETREE_SORT_FOLDER))
        );
    }

    public function testGetFileTreeByNatural()
    {
        $this->assertEquals(
            json_encode([
                'test_tree_sort' => [
                    'a' => [
                        'c.txt' => 1048576,
                        'd.txt' => 1048576
                    ],
                    'z' => [
                        'c.txt' => 1048576,
                    ],
                    'file.txt' => 1048576
                ]
            ]),
            json_encode($this->torrent->getFileTree(TorrentFile::FILETREE_SORT_NATURAL))
        );
    }
}
