<?php

namespace StatonLab\FieldGenerator;


class PathFinder
{
    /**
     * Path to root.
     *
     * @var bool|string
     */
    protected $path;


    /**
     * Max search depth.
     *
     * @var int
     */
    protected $max_depth;

    /**
     * PathFinder constructor.
     *
     * @param int $max_search_depth Number of directories to search. E.g., stop
     *                              searching after 4 steps back from current dir.
     * @param string|null $known_path if the path is known, return it.
     *
     * @return void
     */
    public function __construct($known_path = null, $max_search_depth = 7)
    {
        $this->max_depth = $max_search_depth;

        $this->path = $known_path ?: $this->findRoot();
    }


    /**
     * Find the root path of the Drupal setup.
     *
     * @return bool|string the path to the drupal root or FALSE if not found.
     */
    public function findRoot()
    {
        $path = getcwd();
        $current_path = explode('/', $path);
        $i = 1;

        while ($path !== '/' && $i < $this->max_depth) {
            if (is_dir($path.'/sites/all/modules')) {
                return $path;
            }

            array_pop($current_path);
            $path = implode('/', $current_path);
            $i++;
        }

        return false;
    }

    /**
     * The path to the drupal root or FALSE if not found.
     *
     * @return bool|string
     */
    public function getRoot()
    {
        return $this->path;
    }
}