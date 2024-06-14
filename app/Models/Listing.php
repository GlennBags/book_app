<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
 * @property bool   $isEbook
 * @property int|null $isbn_10
 * @property int|null $isbn_13
 */
class Listing extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function getByAuthor(string $author): Collection
    {
        $listings = Listing::orderBy('publishedDate', 'desc');

        // if the author string is quoted, perform exact match
        if (preg_match('/^(["\']).*\1$/m', $author) === 1) {
            $author = trim($author, "'\"");
        } else {
            $author = str_replace(' ', '%', $author);
        }

        return $listings->where('authors', 'LIKE', "%$author%")->get();
    }
}
