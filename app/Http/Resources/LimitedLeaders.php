<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LimitedLeader extends JsonResource
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
            'user_name' => $this->user_name,
            'email' => $this->email,
            'project_id' => $this->project_id
        ];
    }
}
