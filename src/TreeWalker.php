<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\DefinitionFinder;

class TreeWalker implements TreeDefinitions {
  private Map<string, Set<string>> $classes = Map { };
  private Map<string, Set<string>> $interfaces = Map { };
  private Map<string, Set<string>> $traits = Map { };
  private Map<string, Set<string>> $enums = Map { };
  private Map<string, Set<string>> $types = Map { };
  private Map<string, Set<string>> $newtypes = Map { };
  private Map<string, Set<string>> $functions = Map { };
  private Map<string, Set<string>> $constants = Map { };

  /**
   * Create a TreeWalker
   *
   * @param $root - the root directory
   * @param $path_filter_func - called for every file name before it is parsed.
   *   If it returns false, the file is not parsed. Handy if there's something
   *   in vendor/ or your tests that does not parse.
   */
  public function __construct(
    string $root,
    ?(function(string): bool) $path_filter_func = null,
  ) {
    $dit = new \RecursiveDirectoryIterator($root) /* HH_FIXME[2049] no HHI */;
    $rit = new \RecursiveIteratorIterator($dit) /* HH_FIXME[2049] no HHI */;
    $files = Vector { };
    foreach ($rit as $path => $info) {
      if ($info->isDir() || $info->isLink() || !$info->isReadable()) {
        continue;
      }

      $ext = $info->getExtension();
      if ($ext !== 'php' && $ext !== 'hh') {
        continue;
      }

      if ($path_filter_func !== null) {
        if (!$path_filter_func($path)) {
          continue;
        }
      }

      $files[] = $path;
    }
    foreach ($files as $file) {
      $fp = FileParser::FromFile($file);
      self::addDefs($this->classes, $file, $fp->getClassNames());
      self::addDefs($this->interfaces, $file, $fp->getInterfaces());
      self::addDefs($this->traits, $file, $fp->getTraits());
      self::addDefs($this->enums, $file, $fp->getEnums());
      self::addDefs($this->types, $file, $fp->getTypes());
      self::addDefs($this->newtypes, $file, $fp->getNewtypes());
      self::addDefs($this->functions, $file, $fp->getFunctions());
      self::addDefs($this->constants, $file, $fp->getConstants());
    }
  }

  private static function addDefs(
    Map<string, Set<string>> $container,
    string $file,
    \ConstVector<string> $defs,
  ): void {
    foreach ($defs as $def) {
      if (!$container->containsKey($def)) {
        $container[$def] = Set { };
      }
      $container[$def]->add($file);
    }
  }

  public function getClasses(): \ConstMap<string, Set<string>> { return $this->classes; }
  public function getInterfaces(): \ConstMap<string, Set<string>> { return $this->interfaces; }
  public function getTraits(): \ConstMap<string, Set<string>> { return $this->traits; }
  public function getEnums(): \ConstMap<string, Set<string>> { return $this->enums; }
  public function getTypes(): \ConstMap<string, Set<string>> { return $this->types; }
  public function getNewtypes(): \ConstMap<string, Set<string>> { return $this->newtypes; }
  public function getFunctions(): \ConstMap<string, Set<string>> { return $this->functions; }
  public function getConstants(): \ConstMap<string, Set<string>> { return $this->constants; }
}
