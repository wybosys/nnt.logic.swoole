<?php

namespace Nnt\Server;

use Nnt\Core\DateTime;

class RespFile
{

    /**
     * 普通文件
     * @return RespFile
     */
    static function Regular(string $file, string $typ = null): RespFile
    {
        $r = new RespFile();
        if (!$typ)
            $typ = mime_content_type($file);
        $r->_file = $file;
        $r->type = $typ;
        $r->_stat = stat($file);
        return $r;
    }

    /**
     * @return RespFile
     */
    static function Plain(string $txt, string $typ = null): RespFile
    {
        $r = new RespFile();
        $r->type = $typ;
        $r->_buf = $txt;
        return $r;
    }

    function length(): int
    {
        if ($this->_stat)
            return $this->_stat['size'];
        if ($this->_buf)
            return strlen($this->_buf);
        return 0;
    }

    protected $_file;
    protected $_buf;
    public $type;
    protected $_stat;
    protected $_cachable = true;

    function file(): string
    {
        if ($this->_file)
            return $this->_file;
        if ($this->_downloadfile)
            return $this->_downloadfile;
        return null;
    }

    function stat($key = null)
    {
        if ($key == null)
            return $this->_stat;
        return @$this->_stat[$key];
    }

    function cachable(): bool
    {
        return $this->_stat != null;
    }

    protected $_downloadfile;

    /**
     * @return RespFile
     */
    function asDownload(string $filename)
    {
        $this->_downloadfile = $filename;
        return $this;
    }

    private $_expire;

    /**
     * 过期时间，默认为1年
     * @return \DateTime
     */
    function expire()
    {
        if ($this->_expire)
            return $this->_expire;
        $this->_expire = new \DateTime();
        $this->_expire->setTimestamp((DateTime::Now() + DateTime::YEAR) * 1000);
        return $this->_expire;
    }

    function download()
    {
        return $this->_downloadfile != null;
    }
}