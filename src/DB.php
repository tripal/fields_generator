<?php

namespace StatonLab\FieldGenerator;

use Exception;
use PDO;

class DB
{

    /**
     * Database connection.
     *
     * @var PDO
     */
    protected $connection;

    /**
     * DB Config.
     *
     * @var array
     */
    protected $config;

    /**
     * P
     *
     * @var array
     */
    protected $parameters;

    /**
     * Prepared PDO Statement.
     *
     * @var \PDOStatement
     */
    protected $prepared;

    /**
     * SQL Query.
     *
     * @var string
     */
    protected $sql;

    /**
     * True if DB is not connected.
     *
     * @var bool
     */
    protected $unconnected;


    /**
     * DB constructor.
     */
    public function __construct($drupal_path = null)
    {
        $settings = $this->getSettingsFilePath($drupal_path);

        if ($settings) {
            $this->readSettings($settings);
            $this->createConnection();
        }
    }

    /**
     * Create PDO Connection.
     */
    protected function createConnection()
    {
        $dsn = "{$this->config['driver']}:dbname={$this->config['name']};host={$this->config['host']}";
        $this->connection = new PDO($dsn, $this->config['username'], $this->config['password']);
    }

    /**
     * Get the path to the drupal settings file.
     *
     * @param $path
     *
     * @return bool|string
     */
    protected function getSettingsFilePath($drupal_root)
    {
        // Get the config file
        if (!$drupal_root) {
            $drupal_root = getcwd().'/..';
        }

        // Clear any trailing slashes
        $drupal_root = rtrim($drupal_root, '/');

        $settings = "{$drupal_root}/sites/default/settings.php";

        if (!file_exists($settings)) {

            $this->unconnected = true;

            return false;
        }

        if (!is_readable($settings)) {
            $this->unconnected = true;

            return false;
        }

        return $settings;
    }

    /**
     * Read DB settings from Drupal path.
     *
     * @param $path
     */
    protected function readSettings($path)
    {
        include $path;
        // The $databases variables gets imported from the settings file.
        $settings = $databases['default']['default'];
        $this->config = [
            'name' => $settings['database'],
            'host' => $settings['host'],
            'username' => $settings['username'],
            'password' => $settings['password'],
            'port' => $settings['port'],
            'driver' => $settings['driver'],
            'prefix' => $settings['prefix'],
        ];
    }

    /**
     * Set the SQL query.
     *
     * @param $sql
     * @param $parameters
     *
     * @return $this
     */
    public function query($sql, $parameters = [])
    {
        $this->sql = $sql;
        $this->prepared = $this->connection->prepare($sql);
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get the results.
     *
     * @throws Exception
     * @return mixed
     */
    public function get()
    {
        $execute = $this->prepared->execute($this->parameters);

        if (!$execute) {
            $error = $this->prepared->errorInfo();
            throw new Exception("Couldn't execute query. $this->sql. ".PHP_EOL.implode(' ', $error));
        }

        return $this->prepared->fetchAll();
    }

    /**
     * Get the count directly.
     *
     * @throws Exception
     * @return mixed
     */
    public function count()
    {
        return intval($this->get()[0]['count']);
    }


    /**
     * Inform generator if there is no DB connection
     *
     * @return bool True if no connection exists.
     */
    public function checkOffline()
    {
        return $this->unconnected;
    }
}