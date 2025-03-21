<?php

namespace Lochmueller\Seal\Schema;

use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;

class SchemaBuilder
{
public function getSchema(){
    return new Schema([$this->getPageIndex()]);
}

    public function getPageIndex(){

        return new Index('page', [
            'id' => new Field\IdentifierField('id'),
            'title' => new Field\TextField('title', sortable: true),
            'tags' => new Field\TextField('tags', multiple: true, filterable: true),
            'content' => new Field\TextField('content', searchable: false),
        ]);
    }
}
