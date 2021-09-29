<?php

spl_autoload_register(function ($class) {
    require_once $class . '.php';
});

class CreateSiteMap
{
    private string $root_url;

    private string $version_encoding;
    private string $url_xmlns;
    private $url_list;
    private string $xml;
    private $sitemap;
    private string $mapIndex;

    public function __construct()
    {
    }

    /**
     * @param mixed $root_url 设置根域名
     */
    public function setRootUrl($root_url = 'http://www.shallowdream.net'): static
    {
        $this->root_url = $root_url;
        return $this;
    }


    /**
     * @return mixed 输出根域名
     */
    public function getRootUrl(): mixed
    {
        return $this->root_url;
    }

    /**
     * 设置xml版本和编码
     * @param string $version
     * @param string $encoding
     * @return $this
     */
    public function setXmlVersionAndEncoding($version = '1.0', $encoding = 'UTF-8'): static
    {
        $this->version_encoding = '<?xml version="' . $version . '" encoding="' . $encoding . '"?>';
        return $this;
    }

    /**
     * 输出xml版本和编码
     * @return string
     */
    public function getXmlVersionAndEncoding(): string
    {
        return $this->version_encoding;
    }

    /**
     * 设置url内链
     * @param $url_list
     * @return $this
     */
    public function setUrlLoc($url_list): static
    {
        if (!empty($url_list)) {
            $this->url_list = $url_list;
            return $this;
        }
        die('url不能为空');
    }

    /**
     * 设置sitemap的权威
     * @param float $xmlns
     * @return $this
     */
    public function setUrlXmlns($xmlns = 0.9): static
    {
        $this->url_xmlns = 'xmlns="http://www.sitemaps.org/schemas/sitemap/' . $xmlns . '" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"';
        return $this;
    }

    public function setSiteMap($sitemap): static
    {
        if (!empty($sitemap)) {
            $this->sitemap = $sitemap;
            return $this;
        }
        die('sitemap不能为空');
    }

    /**
     * 设置xml
     * @return $this
     */
    public function setSiteMapUrlLoc(): static
    {
        if (empty($this->url_list)) {
            die('请先设置url内链');
        }
        if (empty($this->url_xmlns)) {
            die('请先设置url权威');
        }
        $this->xml = '';
        $this->xml .= '<urlset ' . $this->url_xmlns . '>';
        foreach ($this->url_list as $url) {
            $this->xml .= '<url><loc>' . $url . '</loc></url>';
        }
        $this->xml .= '</urlset>';
        return $this;
    }

    public function setSiteMapIndex(): static
    {
        if (empty($this->url_xmlns)) {
            die('请先设置url权威');
        }
        if (empty($this->sitemap)) {
            die('请先设置sitemap权威');
        }
        $this->mapIndex = '';
        $this->mapIndex .= '<sitemapindex ' . $this->url_xmlns . '>';
        foreach ($this->sitemap as $map) {
            $this->mapIndex .= '<sitemap><loc>' . $map . '</loc></sitemap>';
        }
        $this->mapIndex .= '</sitemapindex>';
        return $this;
    }

    public function createSiteMapIndex(): string
    {
        return $this->mapIndex;
    }

    public function createSiteMapUrlLoc(): string
    {
        return $this->xml;
    }

}

$siteMap = new CreateSiteMap();
$siteMap->setRootUrl()
    ->setXmlVersionAndEncoding()
    ->setUrlLoc([
        'http://www.shallowdream.net',
        'http://www.shallowdream.net/archive',
        'http://www.shallowdream.net/stores',
        'http://www.shallowdream.net/abouts',
        'http://www.shallowdream.net/single',
    ])
    ->setSiteMap([
        'http://www.shallowdream.net/index.php',
        'http://www.shallowdream.net/main.php'
    ])
    ->setUrlXmlns(0.5)
    ->setSiteMapUrlLoc()
    ->setSiteMapIndex();

echo $siteMap->createSiteMapUrlLoc();

echo $siteMap->createSiteMapIndex();