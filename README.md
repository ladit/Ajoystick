# Ajoystick

Simple Android joystick base on adb for PHP, including some Genyshell operations.

require ext-dom and ext-gd.

## example

```php
<?php
use Ajoystick\Device;

$element = Device::singleton()->waitUntil(function () {
    $element = Device::singleton()->findElementById('element-id');
    if ($element) return [true, $element];
    return [false, null];
});
$element->tap();
Device::singleton()->swipeUp();
Device::singleton()->screenshotAndSaveTo('.');
```
