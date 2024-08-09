<?php

declare( strict_types = 1 );

namespace Northrook\Entity;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class AssociativeEntity extends PersistentEntity implements IteratorAggregate, Countable
{
    public function __construct(
        string $name,
        array  $data,
    ) {
        parent::__construct( $name, $data );
    }

    final public function set( string $key, mixed $value ) : self {
        $this->data[ $key ] = $value;
        return $this;
    }

    final public function get( string $key ) : mixed {
        return $this->data[ $key ];
    }

    final public function has( string $key ) : bool {
        return $this->data[ $key ] ?? false;
    }

    final public function remove( string $key ) : self {
        unset( $this->data[ $key ] );
        return $this;
    }

    public static function hydrate( array $entityArray ) : PersistentEntity {
        $store           = new static( $entityArray[ 'name' ], $entityArray[ 'data' ] );
        $store->readonly = false;
        return $store;
    }

    public function getIterator() : Traversable {
        return new ArrayIterator( $this->data );
    }

    public function count() : int {
        return count( $this->data );
    }
}