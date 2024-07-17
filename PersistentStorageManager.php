<?php

namespace Northrook;

use Northrook\Storage\PersistentEntity;
use Northrook\Storage\PersistentEntityInterface;
use Northrook\Core\Trait\SingletonClass;
use Northrook\Filesystem\File;
use Northrook\Logger\Log;
use function Northrook\Core\getProjectRootDirectory;
use function Northrook\Core\normalizeKey;
use function Northrook\Core\normalizePath;

class PersistentStorageManager
{
    use SingletonClass;

    private readonly string $storageDirectory;

    private array $loadedDataStores = [];

    public function __construct(
        ?string $storageDirectory = null,
    ) {
        $this->instantiationCheck();

        $this->storageDirectory = normalizePath(
            $storageDirectory ?? $this->systemTempDirectory(),
        );

        $this::$instance = $this;
    }


    /**
     * @template PersistentResource
     * @param class-string<PersistentResource>  $name
     *
     * @return PersistentResource|null
     */
    public function getResource( string $name ) : ?object {

        $data  = $this->loadPersistentResource( $name );
        $store = $data[ 'generator' ] ?? false;

        if ( is_subclass_of( $store, PersistentEntityInterface::class ) ) {
            dump( $store );
            return $this->loadedDataStores[ $name ] ??= $store::hydrate( $data );
        }
        else {
            return null;
        }
    }

    /**
     * Retrieves the raw data from a {@see PersistentEntity}.
     *
     * - The data is readonly.
     *
     * @param string  $name
     *
     * @return null|array|object
     */
    public function readResourceData( string $name ) : mixed {
        if ( isset( $this->loadedDataStores[ $name ] ) ) {
            return iterator_to_array( $this->loadedDataStores[ $name ] );
        }
        return $this->loadPersistentResource( $name )[ 'data' ] ?? null;
    }


    private function loadPersistentResource( string $name ) : ?array {
        $dataFile = $this->getResourcePath( $name );
        if ( !$dataFile->exists ) {
            return null;
        }
        return include $dataFile;
        // return $this->loadedDataStores[ $name ] ??= require_once $dataFile->path;
    }

    private function getResourcePath( string $name ) : File {
        $filename = normalizeKey( $name );
        return new File( "$this->storageDirectory/$filename" . PersistentEntity::FILE_EXTENSION );
    }

    public static function getStorageDirectory() : string {
        return self::getInstance()->storageDirectory;
    }

    /**
     * Retrieve the current system temp directory, with a hash of the project root appended.
     *
     * @return string
     */
    private function systemTempDirectory() : string {
        Log::notice(
            "The {manager} is using the {fallback}. Please ensure this is intentional.",
            [
                'manager'  => $this::class,
                'fallback' => 'systemTempDirectory',
            ],
        );
        return sys_get_temp_dir() . '/' . hash( 'xxh3', getProjectRootDirectory() );
    }
}