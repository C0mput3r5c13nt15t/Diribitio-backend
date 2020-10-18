<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LimitedStudent extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // return parent::toArray($request);

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'grade' => $this->grade,
            'letter' => $this->letter,
            'project_id' => $this->project_id,
            'role' => $this->role
        ];
    }
}
