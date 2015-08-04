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

class FileParser {
  private ScannedScope $defs;

  private function __construct(
    private string $file,
    TokenQueue $tq,
  ) {
    $this->defs = (new ScopeConsumer($tq))
      ->getBuilder()
      ->setPosition(shape('filename' => $file))
      ->build();
  }

  ///// Constructors /////

  public static function FromFile(
    string $filename,
  ): FileParser {
    return self::FromData(file_get_contents($filename), $filename);
  }

  public static function FromData(
    string $data,
    ?string $filename = null,
  ): FileParser {
    return new FileParser(
      $filename === null ? '__DATA__' : $filename,
      new TokenQueue($data),
    );
  }

  ///// Accessors /////

  public function getFilename(): string { return $this->file; }

  public function getClasses(): \ConstVector<ScannedBasicClass> {
    return $this->defs->getClasses();
  }
  public function getInterfaces(): \ConstVector<ScannedInterface> {
    return $this->defs->getInterfaces();
  }
  public function getTraits(): \ConstVector<ScannedTrait> {
    return $this->defs->getTraits();
  }
  public function getFunctions(): \ConstVector<ScannedFunction> {
    return $this->defs->getFunctions();
  }
  public function getConstants(): \ConstVector<ScannedConstant> {
    return $this->defs->getConstants();
  }
  public function getEnums(): \ConstVector<ScannedEnum> {
    return $this->defs->getEnums();
  }
  public function getTypes(): \ConstVector<ScannedType> {
    return $this->defs->getTypes();
  }
  public function getNewtypes(): \ConstVector<ScannedNewtype> {
    return $this->defs->getNewtypes();
  }

  ///// Convenience /////

  public function getClassNames(): \ConstVector<string> {
    return $this->getClasses()->map($class ==> $class->getName());
  }
  
  public function getInterfaceNames(): \ConstVector<string> {
    return $this->getInterfaces()->map($x ==> $x->getName());
  }

  public function getTraitNames(): \ConstVector<string> {
    return $this->getTraits()->map($x ==> $x->getName());
  }

  public function getFunctionNames(): \ConstVector<string> {
    return $this->getFunctions()->map($class ==> $class->getName());
  }

  public function getConstantNames(): \ConstVector<string> {
    return $this->getConstants()->map($constant ==> $constant->getName());
  }

  public function getEnumNames(): \ConstVector<string> {
    return $this->getEnums()->map($x ==> $x->getName());
  }

  public function getTypeNames(): \ConstVector<string> {
    return $this->getTypes()->map($x ==> $x->getName());
  }

  public function getNewtypeNames(): \ConstVector<string> {
    return $this->getNewtypes()->map($x ==> $x->getName());
  }
}
