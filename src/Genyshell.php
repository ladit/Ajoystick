<?php


namespace Ajoystick;

/**
 * Trait Genyshell
 * @package Ajoystick
 */
trait Genyshell
{
    /**
     * genyshell executive path
     *
     * @var string
     */
    protected $genyshellExecutive = 'genyshell';

    /**
     * @param string $genyshellExecutive
     */
    public function setGenyshellExecutive(string $genyshellExecutive)
    {
        $this->genyshellExecutive = $genyshellExecutive;
    }

    /**
     * @return string
     */
    public function getGenyshellExecutive(): string
    {
        return $this->genyshellExecutive;
    }

    /**
     * genyshell command
     *
     * @param string $command
     * @param bool $noBlocking
     * @return mixed
     */
    public function genyShell(string $command, bool $noBlocking = false)
    {
        $c = $this->getGenyshellExecutive() . " -c \"$command\"";
        Log::debug("Genyshell:exec: $c");
        exec($c, $output);
        return implode(PHP_EOL, array_slice($output, 5));
    }

    /**
     * @return bool
     */
    public function getGPSStatus()
    {
        $info = explode(': ', $this->genyShell('gps getstatus'));
        return $info[1] === 'enabled';
    }

    public function enableGPS()
    {
        $this->genyShell('gps setstatus enabled');
    }

    public function disableGPS()
    {
        $this->genyShell('gps setstatus disabled');
    }

    /**
     * @param float|null $longitude
     * @param float|null $latitude
     */
    public function setGPS(float $longitude = null, float $latitude = null)
    {
        if (!$this->getGPSStatus()) $this->enableGPS();
        if ($longitude) $this->genyShell("gps setlongitude $longitude");
        if ($latitude) $this->genyShell("gps setlatitude $latitude");
    }

    /**
     * @return array
     */
    public function getGPS()
    {
        $latitude = explode(': ', $this->genyShell("gps getlatitude"));
        $longitude = explode(': ', $this->genyShell("gps getlongitude"));
        return ['latitude' => floatval($latitude[1]), 'longitude' => floatval($longitude[1])];
    }
}
