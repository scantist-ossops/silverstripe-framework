<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\ConfigLoader;
use SilverStripe\Core\Config\CoreConfigFactory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ClassManifest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\View\ThemeManifest;

/**
 * Facilitates putting the system into a testable state.
 * Separated from test definition classes like {@link SapphireTest}
 * in order to allow for independant use. Specifically, this enables
 * checking if the system is in a test state without requiring inclusion
 * of underlying dev dependencies like PHPUnit.
 *
 * This class is designed to be used as a singleton,
 * call with TestHelpers::inst().
 */
class TestHelpers
{

    /**
     * @var boolean
     */
    protected $isRunningTest = false;

    /**
     * @var ClassManifest
     */
    protected $testClassManifest;

    /**
     * Determines if unit tests are currently run, flag set during test bootstrap.
     * This is used as a cheap replacement for fully mockable state
     * in certain contiditions (e.g. access checks).
     * Caution: When set to FALSE, certain controllers might bypass
     * access checks, so this is a very security sensitive setting.
     *
     * @return boolean
     */
    public function isRunningTest()
    {
        return $this->isRunningTest;
    }

    /**
     * @param $bool
     */
    public function setIsRunningTest($bool)
    {
        $this->isRunningTest = $bool;
    }

    /**
     * Set the manifest to be used to look up test classes by helper functions
     *
     * @param ClassManifest $manifest
     */
    public function setTestClassManifest($manifest)
    {
        $this->testClassManifest = $manifest;
    }

    /**
     * Return the manifest being used to look up test classes by helper functions
     *
     * @return ClassManifest
     */
    public function getTestClassManifest()
    {
        return $this->testClassManifest;
    }

    /**
     * Pushes a class and template manifest instance that include tests onto the
     * top of the loader stacks.
     */
    public function useTestManifest()
    {
        $flush = !empty($_GET['flush']);
        $classManifest = new ClassManifest(
            BASE_PATH,
            true,
            $flush
        );

        $this->getClassLoader()->pushManifest($classManifest, false);
        $this->setTestClassManifest($classManifest);

        $this->getThemeResourceLoader()->addSet('$default', new ThemeManifest(
            BASE_PATH,
            project(),
            true,
            $flush
        ));

        // Once new class loader is registered, push a new uncached config
        $config = $this->getCoreConfigFactory()->createCore();
        $this->getConfigLoader()->pushManifest($config);

        // Invalidate classname spec since the test manifest will now pull out new subclasses for each internal class
        // (e.g. Member will now have various subclasses of DataObjects that implement TestOnly)
        DataObject::reset();
    }

    /**
     * Returns true if we are currently using a temporary database
     */
    public function usingTempDb()
    {
        $dbConn = $this->getDatabaseConnection();
        $prefix = getenv('SS_DATABASE_PREFIX') ?: 'ss_';
        return $dbConn && (substr($dbConn->getSelectedDatabase(), 0, strlen($prefix) + 5)
                == strtolower(sprintf('%stmpdb', $prefix)));
    }

    public function killTempDb()
    {
        // Delete our temporary database
        if ($this->usingTempDb()) {
            $dbConn = $this->getDatabaseConnection();
            $dbName = $dbConn->getSelectedDatabase();
            if ($dbName && $this->getDatabaseConnection()->databaseExists($dbName)) {
                // Some DataExtensions keep a cache of information that needs to
                // be reset whenever the database is killed
                foreach (ClassInfo::subclassesFor('SilverStripe\\ORM\\DataExtension') as $class) {
                    $toCall = array($class, 'on_db_reset');
                    if (is_callable($toCall)) {
                        call_user_func($toCall);
                    }
                }

                // echo "Deleted temp database " . $dbConn->currentDatabase() . "\n";
                $dbConn->dropSelectedDatabase();
            }
        }
    }

    /**
     * Remove all content from the temporary database.
     */
    public function emptyTempDb()
    {
        if ($this->usingTempDb()) {
            $this->getDatabaseConnection()->clearAllData();

            // Some DataExtensions keep a cache of information that needs to
            // be reset whenever the database is cleaned out
            $classes = array_merge(ClassInfo::subclassesFor('SilverStripe\\ORM\\DataExtension'), ClassInfo::subclassesFor('SilverStripe\\ORM\\DataObject'));
            foreach ($classes as $class) {
                $toCall = array($class, 'on_db_reset');
                if (is_callable($toCall)) {
                    call_user_func($toCall);
                }
            }
        }
    }

    public function createTempDb()
    {
        // Disable PHPUnit error handling
        restore_error_handler();

        // Create a temporary database, and force the connection to use UTC for time
        global $databaseConfig;
        $databaseConfig['timezone'] = '+0:00';
        DB::connect($databaseConfig);
        $dbConn = $this->getDatabaseConnection();
        $prefix = getenv('SS_DATABASE_PREFIX') ?: 'ss_';
        $dbname = strtolower(sprintf('%stmpdb', $prefix)) . rand(1000000, 9999999);
        while (!$dbname || $dbConn->databaseExists($dbname)) {
            $dbname = strtolower(sprintf('%stmpdb', $prefix)) . rand(1000000, 9999999);
        }

        $dbConn->selectDatabase($dbname, true);

        $this->resetDBSchema();

        // Reinstate PHPUnit error handling
        set_error_handler(array('PHPUnit_Util_ErrorHandler', 'handleError'));

        return $dbname;
    }

    public function deleteAllTempDbs()
    {
        $prefix = getenv('SS_DATABASE_PREFIX') ?: 'ss_';
        foreach (DB::get_schema()->databaseList() as $dbName) {
            if (preg_match(sprintf('/^%stmpdb[0-9]+$/', $prefix), $dbName)) {
                DB::get_schema()->dropDatabase($dbName);
                if (Director::is_cli()) {
                    echo "Dropped database \"$dbName\"" . PHP_EOL;
                } else {
                    echo "<li>Dropped database \"$dbName\"</li>" . PHP_EOL;
                }
                flush();
            }
        }
    }

    /**
     * Reset the testing database's schema.
     * @param bool $includeExtraDataObjects If true, the extraDataObjects tables will also be included
     */
    public function resetDbSchema($includeExtraDataObjects = false)
    {
        if ($this->usingTempDb()) {
            DataObject::reset();

            // clear singletons, they're caching old extension info which is used in DatabaseAdmin->doBuild()
            Injector::inst()->unregisterAllObjects();

            $dataClasses = ClassInfo::subclassesFor(DataObject::class);
            array_shift($dataClasses);

            DB::quiet();
            $schema = DB::get_schema();
            $extraDataObjects = $includeExtraDataObjects ? static::getExtraDataObjects() : null;
            $schema->schemaUpdate(function () use ($dataClasses, $extraDataObjects) {
                foreach ($dataClasses as $dataClass) {
                    // Check if class exists before trying to instantiate - this sidesteps any manifest weirdness
                    if (class_exists($dataClass)) {
                        $SNG = singleton($dataClass);
                        if (!($SNG instanceof TestOnly)) {
                            $SNG->requireTable();
                        }
                    }
                }

                // If we have additional dataobjects which need schema, do so here:
                if ($extraDataObjects) {
                    foreach ($extraDataObjects as $dataClass) {
                        $SNG = singleton($dataClass);
                        if (singleton($dataClass) instanceof DataObject) {
                            $SNG->requireTable();
                        }
                    }
                }
            });

            ClassInfo::reset_db_cache();
            DataObject::singleton()->flushCache();
        }
    }

    /**
     * @todo Replace with injected services via the new App object (#6681)
     * @return ClassLoader
     */
    protected function getClassLoader()
    {
        return ClassLoader::instance();
    }

    /**
     * @todo Replace with injected services via the new App object (#6681)
     * @return ThemeResourceLoader
     */
    protected function getThemeResourceLoader()
    {
        return ThemeResourceLoader::instance();
    }

    /**
     * @todo Replace with injected services via the new App object (#6681)
     * @return CoreConfigFactory
     */
    protected function getCoreConfigFactory()
    {
        return CoreConfigFactory::inst();
    }

    /**
     * @todo Replace with injected services via the new App object (#6681)
     * @return ConfigLoader
     */
    protected function getConfigLoader()
    {
        return ConfigLoader::instance();
    }

    /**
     * @todo Replace with injected services via the new App object (#6681)
     * @return \SilverStripe\ORM\Connect\Database
     */
    protected function getDatabaseConnection()
    {
        return DB::get_conn();
    }
}