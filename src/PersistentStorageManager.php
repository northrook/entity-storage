<?php

declare( strict_types = 1 );

namespace Northrook;

use Northrook\Core\Trait\SingletonClass;
use Northrook\Entity\PersistentEntity;
use Northrook\Entity\EntityInterface;
use Northrook\Resource\Path;
use Psr\Log\LoggerInterface;

final class PersistentStorageManager
{
    use SingletonClass;

    private readonly string $storageDirectory;

    private array $loadedDataStores = [];

    public function __construct(
        ?string                           $storageDirectory = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly bool             $expectSystemCacheDirectory = false,
    ) {
        $this->instantiationCheck();

        $this->setStorageDirectory( $storageDirectory );

        $this::$instance = $this;
    }

    private function setStorageDirectory( ?string $path ) : void {
        if ( $path ) {
            $this->storageDirectory = normalizePath( $path );
            return;
        }

        if ( !$this->expectSystemCacheDirectory ) {

            if ( !$this->logger ) {
                throw new \UnexpectedValueException(
                    'The ' . $this::class . ' was not provided with a storage directory.
                Either pass a valid path, or set `expectSystemCacheDirectory: true`.',
                );
            }

            $this->logger?->warning(
                "The {manager} is using the {fallback}. Please ensure this is intentional. You can set {argument} to {bool} in the constructor to allow this.",
                [
                    'manager'  => $this::class,
                    'fallback' => 'systemTempDirectory',
                    'argument' => 'expectSystemCacheDirectory',
                    'bool'     => 'true',
                ],
            );
        }

        $this->storageDirectory = getSystemCacheDirectory();
    }

    /**
     * @template PersistentResource
     * @param class-string<PersistentResource>  $name
     *
     * @return PersistentResource|null
     */
    public function getResource( string $name ) : ?object {

        if ( isset( $this->loadedDataStores[ $name ] ) ) {
            return $this->loadedDataStores[ $name ];
        }

        $data  = $this->loadPersistentResource( $name );
        $store = $data[ 'generator' ] ?? false;

        if ( \is_subclass_of( $store, EntityInterface::class ) ) {
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
            return \iterator_to_array( $this->loadedDataStores[ $name ] );
        }
        return $this->loadPersistentResource( $name )[ 'data' ] ?? null;
    }


    private function loadPersistentResource( string $name ) : ?array {
        $dataFile = $this->getResourcePath( $name );
        if ( !$dataFile->exists ) {
            return null;
        }
        return include $dataFile->path;
        // return $this->loadedDataStores[ $name ] ??= require_once $dataFile->path;
    }

    private function getResourcePath( string $name ) : Path {
        $filename = PersistentEntity::getFileName( $name );
        return new Path( "$this->storageDirectory/$filename" );
    }

    public static function getStorageDirectory() : string {
        return self::getInstance()->storageDirectory;
    }
}