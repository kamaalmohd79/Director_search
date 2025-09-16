<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchResult extends Model
{
    protected $fillable = [
        'search_log_id',
        'searchedPeopleId','searchedFirstName','searchedSurname','searchedFullName','searchedDOB',
        'peopleId','score','firstName','lastName','status','dateOfBirth',
        'localDirectorNumber','isOriginalDirector','companyId','companyName','companyNumber','safeNumber','companyType',
        'address','addressCity','addressPostCode','addressHouseNo',
    ];

    public function searchLog()
    {
        return $this->belongsTo(\App\Models\SearchLog::class);
    }
}
