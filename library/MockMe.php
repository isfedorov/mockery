<?php

class MockMe
{

    protected $_className = '';

    protected $_mockClassName = '';

    public function __construct($className, $customName = null) 
    {
        $this->_className = $className;
        if (!is_null($customName)) {
            $this->setMockClassName($customName);
        } else {
            $this->setMockClassName('MockMe_' . sha1(microtime()));
        }
    }

    public static function mock($className, $customName = null) 
    {
        $mockme = new self($className, $customName);
        $mockme->createMockObject();
        $reflectedClass = new ReflectionClass($mockme->getMockClassName());

        $constructor = null;
        if ($reflectedClass->hasMethod('__construct')) {
            $constructor = '__construct';
        // ensure enforced in class definition
        } elseif ($reflectedClass->hasMethod($mockme->getMockClassName())) {
            $constructor = $mockme->getMockClassName();
        }
        
        $mockObject = null;
        if (!is_null($constructor)) {
            $constructMethod = $reflectedClass->getMethod($constructor);
            $constructParams = $constructMethod->getParameters();
            if (count($constructParams) == 0) {
                $mockObject = $reflectedClass->newInstance();
            } else {
                $params = array();
                foreach ($constructParams as $param) {
                    if ($param->isOptional()) {
                        $params[] = null;
                        continue;
                    }
                    if ($param->isArray()) {
                        $params[] = array();
                        continue;
                    }
                    $classHint = $param->getClass();
                    if ($classHint) {
                        $params[] = $classHint->newInstance();
                        continue;
                    }
                    $params[] = null; // default - just give it anything
                }
                $mockObject = $reflectedClass->newInstanceArgs($params);
            }
        } else {
            $mockObject = $reflectedClass->newInstance();
        }
        return $mockObject;
    }

    public function getClassName() 
    {
        return $this->_className;
    }

    public function setMockClassName($name) 
    {
        $this->_mockClassName = $name;
    }

    public function getMockClassName() 
    {
        if ($this->_mockClassName == '') {
            throw new Exception('The class name of the Test Double has not yet been set');
        }
        return $this->_mockClassName;
    }

    public function createMockObject() 
    {
        $definition = $this->_createDefinition();
        eval($definition);
    }

    protected function _createDefinition() 
    {
        $className = $this->getClassName();
        $mockClassName = $this->getMockClassName();
        if (!class_exists($className, true) && !interface_exists($className, true)) {
            throw new MockMe_Exception('Class or interface ' . $className . ' does not exist or has not been included');
        }
        $reflectedClass = new ReflectionClass($className);
        if ($reflectedClass->isFinal()) {
            throw new MockMe_Exception('Unable to create a Test Double for a class marked final');
        }
        $inheritance = '';
        $definition = '';
        if ($reflectedClass->isInterface()) {
            $inheritance = ' implements ' . $className;
        } else {
            $inheritance = ' extends ' . $className;
        }
        $definition .= 'class ' . $mockClassName .  $inheritance . '{';
        $definition .= '}';
        return $definition;
    }

}