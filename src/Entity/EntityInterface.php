<?php

declare( strict_types = 1 );

namespace Northrook\Entity;

interface EntityInterface
{
    public static function hydrate( array $entityArray ) : self;

    public function save() : void;

    public function getFilePath() : string;

    public function exists() : bool;
}