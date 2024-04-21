<?php

namespace App\DTO;

class GoogleBookVolume
{
    public ?string $authors; // array / json
    public ?string $title;
    public ?string $subtitle;
    public ?string $categories; // array / json
    public ?string $volumeLink;
    public ?string $infoLink;
    public ?string $previewLink;
    public ?string $imageLinks_thumbnail; // imageLinks->thumbnail
    public ?string $publishedDate;

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
    ];

    /*
     * NEED TO FINISH DTO
    */

    public function __construct(object $data)
    {
        $info = $data->volumeInfo;

        $setProperty = function($value, $property, $type) {
            if ($type === 'string') {
                return $value;
            } elseif ($type === 'array') {
                return is_array($value) ? json_encode($value) : $value;
            } else {
                throw new \Exception("Invalid property type found: `$type`");
            }
        };

        foreach ($this->properties as $property => $type) {
            if (!str_contains($property, '_')) {
                // if no '_', simple property to property
                $this->$property = $setProperty($info->$property, $property, $type);
            } else {
                // otherwise, need to split
                $deepProps = explode('_', $property);
                $final = $info;
                $end   = end($deepProps);
                foreach ($deepProps as $deepProp) {
                    if ($deepProp === $end) {
                        $this->$property = $setProperty($final->$deepProp, $property, $type);
                    } else {
                        if (!isset($final->$deepProp)) dd($info, $deepProp, $final);
                        $final = $final->$deepProp;
                    }
                }
            }
        }
    }
}
