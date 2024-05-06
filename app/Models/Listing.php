<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $authors
 * @property string $title
 * @property string $subtitle
 * @property string $description
 * @property string $categories
 * @property string $canonicalVolumeLink
 * @property string $infoLink
 * @property string $previewLink
 * @property string $imageLinks_thumbnail
 * @property string $publishedDate
 * @property int|null $isbn_10
 * @property int|null  $isbn_13
 */
class Listing extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];
}
