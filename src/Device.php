<?php


namespace Ajoystick;


use DOMXPath;

/**
 * Class Device
 * @package Ajoystick
 */
class Device extends ADB
{
    private static $singletons;

    public $deviceId;
    public $deviceModel;
    public $androidVersion;

    /**
     * whether dump UI hierarchy before find elements
     *
     * @var bool
     */
    public $dump = true;

    /**
     * todo: whether unset previous found elements before find elements
     *
     * @var bool
     */
    public $unsetPreviousElements = true;

    /**
     * current xpath executive
     *
     * @var DOMXPath
     */
    protected $xpath;

    /**
     * Device constructor.
     *
     * @param string|null $deviceId
     */
    public function __construct(string $deviceId = null)
    {
        parent::__construct();
        if ($deviceId) $this->setTargetDevice($deviceId);
        $this->deviceId = parent::getDeviceID();
        $this->deviceModel = parent::getDeviceModel();
        $this->androidVersion = parent::getAndroidVersion();
        $this->sdkVersion = parent::getSdkVersion();
        $resolution = parent::getScreenResolution();
        $this->width = $resolution['width'];
        $this->height = $resolution['height'];
    }

    /**
     * get specific device's singleton of this class
     *
     * @param string|null $deviceId
     * @return $this
     */
    public static function singleton(string $deviceId = null)
    {
        $hash = $deviceId === null ? 'default' : serialize($deviceId);
        if (!isset(self::$singletons[$hash]) or !(self::$singletons[$hash] instanceof Device)) {
            self::$singletons[$hash] = new Device($deviceId);
        }
        return self::$singletons[$hash];
    }

    /**
     * get screen resolution
     *
     * @param bool $refresh
     * @return array
     */
    public function getScreenResolution(bool $refresh = false): array
    {
        if ($refresh or $this->width === null or $this->height === null) {
            $resolution = parent::getScreenResolution();
            $this->width = $resolution['width'];
            $this->height = $resolution['height'];
        }
        return ['width' => $this->width, 'height' => $this->height];
    }

    /**
     * find element, return null rather throw an exception when element not found
     *
     * @param string $type
     * @param string $value
     * @return Element|null
     */
    public function findElementBy(string $type, string $value)
    {
        if ($this->dump) $this->xpath = $this->UIDump();
        $query = $type === 'xpath' ? $value : "(//node[@$type='$value'])[1]";
        $nodeList = $this->xpath->query($query);
        if ($nodeList->length === 0) return null;
        $node = $nodeList->item(0);
        return new Element($node, $this);
    }

    /**
     * find elements
     *
     * @param string $type
     * @param string $value
     * @return ElementList
     */
    public function findElementsBy(string $type, string $value): ElementList
    {
        if ($this->dump) $this->xpath = $this->UIDump();
        $query = $type === 'xpath' ? $value : "//node[@$type='$value']";
        $nodeList = $this->xpath->evaluate($query);
        return new ElementList($nodeList, $this);
    }

    /**
     * find element by name, alias of findElementByText
     *
     * @param string $name
     * @return Element|null
     */
    public function findElementByName(string $name)
    {
        return $this->findElementByText($name);
    }

    /**
     * find elements by name, alias of findElementsByText
     *
     * @param string $name
     * @return ElementList
     */
    public function findElementsByName(string $name): ElementList
    {
        return $this->findElementsByText($name);
    }

    /**
     * find element by text
     *
     * @param string $text
     * @return Element|null
     */
    public function findElementByText(string $text)
    {
        return $this->findElementBy('text', $text);
    }

    /**
     * find elements by text
     *
     * @param string $text
     * @return ElementList
     */
    public function findElementsByText(string $text): ElementList
    {
        return $this->findElementsBy('text', $text);
    }

    /**
     * find element by class
     *
     * @param string $class
     * @return Element|null
     */
    public function findElementByClass(string $class)
    {
        return $this->findElementBy('class', $class);
    }

    /**
     * find elements by class
     *
     * @param string $class
     * @return ElementList
     */
    public function findElementsByClass(string $class): ElementList
    {
        return $this->findElementsBy('class', $class);
    }

    /**
     * find element by resource-id
     *
     * @param string $id
     * @return Element|null
     */
    public function findElementById(string $id)
    {
        return $this->findElementBy('resource-id', $id);
    }

    /**
     * find elements by resource-id
     *
     * @param string $id
     * @return ElementList
     */
    public function findElementsById(string $id): ElementList
    {
        return $this->findElementsBy('resource-id', $id);
    }

    /**
     * find element by content-desc
     *
     * @param string $contentDesc
     * @return Element|null
     */
    public function findElementByContentDesc(string $contentDesc)
    {
        return $this->findElementBy('content-desc', $contentDesc);
    }

    /**
     * find elements by content-desc
     *
     * @param string $contentDesc
     * @return ElementList
     */
    public function findElementsByContentDesc(string $contentDesc): ElementList
    {
        return $this->findElementsBy('content-desc', $contentDesc);
    }

    /**
     * find element by xpath
     *
     * @param string $pattern
     * @return Element|null
     */
    public function findElementByXPath(string $pattern)
    {
        return $this->findElementBy('xpath', $pattern);
    }

    /**
     * find elements by xpath
     *
     * @param string $pattern
     * @return ElementList
     */
    public function findElementsByXPath(string $pattern): ElementList
    {
        return $this->findElementsBy('xpath', $pattern);
    }

}
