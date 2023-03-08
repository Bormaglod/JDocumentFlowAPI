<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Core;

class Relationship {
   private $id;
   private $type;
   private $include;
   private $attrs;

   function __construct(string $id, string $type, string $include, array $attrs) {
      $this->type = $type;
      $this->id = $id;
      $this->include = $include;
      $this->attrs = $attrs;
   }

   function getId(): string {
      return $this->id;
   }

   function getRelationships(): array {
      $data = [ 'data' => [ 'type' => $this->type, 'id' => $this->id]];
      return [ $this->include => $data ];
   }

   function getInclude(): array {
      return [
         'type' => $this->type, 
         'id' => $this->id, 
         'attributes' => array_filter($this->attrs, function($x) { return $x != 'id'; }, ARRAY_FILTER_USE_KEY),
         'links' => [ 'self' => 'http://' . gethostname() . ':8081/' . $this->type . '/' . $this->id]
      ];
   }
}

?>