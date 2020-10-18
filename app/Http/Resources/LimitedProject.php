<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LimitedProject extends JsonResource
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
            'title' => $this->title,
            'leader_id' => $this->leader_id,
            'leaders_type' => $this->leaders_type,
            'min_participants' => $this->min_participants,
            'max_participants' => $this->max_participants,
            'participants' => $this->participants
        ];
    }
}
