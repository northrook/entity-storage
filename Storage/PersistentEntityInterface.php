<?php

declare( strict_types = 1 );

namespace Northrook\Storage;

interface PersistentEntityInterface
{
    public static function hydrate( array $resource ) : self;

    public function save() : void;

    public function getFilePath() : string;

    public function exists() : bool;
}