<?php

namespace App\DTO;

use Exception;

class GoogleBookVolume
{
    public ?string  $authors; // array / json
    public ?string  $title;
    public ?string  $subtitle;
    public ?string  $description;
    public ?string  $categories; // array / json
    public ?string  $canonicalVolumeLink;
    public ?string  $infoLink;
    public ?string  $previewLink;
    public ?string  $imageLinks_thumbnail; // imageLinks->thumbnail
    public ?string  $publishedDate;
    public int|null $ISBN_10;
    public int|null $ISBN_13;

    private array $validTypes = ['int', 'string', 'array',];

    private array $properties = [
        'authors'                => 'array',
        'title'                  => 'string',
        'subtitle'               => 'string',
        'description'            => 'string',
        'categories'             => 'array',
        'canonicalVolumeLink'    => 'string',
        'infoLink'               => 'string',
        'previewLink'            => 'string',
        'imageLinks_thumbnail'   => 'string',
        'publishedDate'          => 'string',
        'ISBN_10'                => 'int',
        'ISBN_13'                => 'int',
    ];

    public function __construct(object $data)
    {
        $info = $data->volumeInfo;

        $setProperty = function($value, $property, $type) {
            if ($type === 'string') {
                return (string)$value;
            } elseif ($type === 'int') {
                return (int)$value;
            } elseif ($type === 'array') {
                return is_array($value) ? implode(', ', $value) : $value;
            } else {
                throw new Exception("Invalid property type found: `$type`");
            }
        };

        foreach ($this->properties as $property => $type) {
            if (!str_contains($property, '_')) {
                // if no '_', simple property to property
                $this->$property = $setProperty($info->$property ?? '', $property, $type);
            } elseif(str_contains($property, 'ISBN')) {
                // is this book isn't released, won't have ISBN's yet
                if (!isset($info->industryIdentifiers)) {
                    $this->$property = null;
                } else {
                    $iiType = $info->industryIdentifiers;
                    $value = $iiType[0]?->type === $property ? ($iiType[0]->identifier ?? null) : ($iiType[1]->identifier ?? null);
                    $this->$property = (int)$value;
                }
            } else {
                // otherwise, need to split
                $deepProps = explode('_', $property);
                $final = $info;
                $end   = end($deepProps);
                foreach ($deepProps as $deepProp) {
                    if ($deepProp === $end) {
                        $this->$property = $setProperty($final->$deepProp, $property, $type);
                    } else {
                        if (!isset($final->$deepProp)) {
                            // if we don't find the property, then set a default
                            $this->$property = $type === 'string' ? '' : [];
                            break;
                        }
                        $final = $final->$deepProp;
                    }
                }
            }
        }

        // found an extra long description, so
        if (!isset($this->description)) $this->description = '';
        if (strlen($this->description) > 2056) {
            $this->description = substr($this->description, 0, 2056);
        }
    }

    public function toArray(): array
    {
        $data = [];

        foreach ($this->properties as $key => $type) {
            $data[$key] = $this->$key;
        }

        return $data;
    }
}
