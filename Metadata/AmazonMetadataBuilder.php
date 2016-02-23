<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\MediaBundle\Metadata;

use Sonata\MediaBundle\Metadata\MetadataBuilderInterface;
use Sonata\MediaBundle\Model\MediaInterface;

use Guzzle\Http\Mimetypes;
use AmazonS3;

class AmazonMetadataBuilder implements MetadataBuilderInterface
{

    protected $settings;

    protected $acl = array(
        'private'            => AmazonS3::ACL_PRIVATE,
        'public'             => AmazonS3::ACL_PUBLIC,
        'open'               => AmazonS3::ACL_OPEN,
        'auth_read'          => AmazonS3::ACL_AUTH_READ,
        'owner_read'         => AmazonS3::ACL_OWNER_READ,
        'owner_full_control' => AmazonS3::ACL_OWNER_FULL_CONTROL,
    );

    /**
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get data passed from the config
     *
     * @return array
     */
    protected function getDefaultMetadata()
    {
        //merge acl
        $output = array();
        if (isset($this->settings['acl']) && !empty($this->settings['acl'])) {
            $output['ACL'] = $this->acl[$this->settings['acl']];
        }

        //merge storage
        if (isset($this->settings['storage'])) {
            if ($this->settings['storage'] == 'standard') {
                $output['storage'] = AmazonS3::STANDARD;
            } elseif ($this->settings['storage'] == 'reduced') {
                $output['storage'] = AmazonS3::REDUCED;
            }
        }

        //merge meta
        if (isset($this->settings['meta']) && !empty($this->settings['meta'])) {
            $output['meta'] = $this->settings['meta'];
        }

        //merge cache control header
        if (isset($this->settings['cache_control']) && !empty($this->settings['cache_control'])) {
            $output['headers']['Cache-Control'] = $this->settings['cache_control'];
        }

        //merge encryption
        if (isset($this->settings['encryption']) && !empty($this->settings['encryption'])) {
            if ($this->settings['encryption'] == 'aes256') {
                $output['encryption'] = 'AES256';
            }
        }

        return $output;
    }

    /**
     * Gets the correct content-type
     *
     * @param string $filename
     *
     * @return array
     */
    protected function getContentType($filename)
    {
        $extension   = pathinfo($filename, PATHINFO_EXTENSION);
        $contentType = Mimetypes::getInstance()->fromExtension($extension);

        return array('contentType' => $contentType);
    }

    /**
     * {@inheritdoc}
     */
    public function get(MediaInterface $media, $filename)
    {
        return array_replace_recursive(
            $this->getDefaultMetadata(),
            $this->getContentType($filename)
        );
    }
}
