<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\SignUpToken as SignUpTokenModel;
use Illuminate\Support\Facades\Crypt;

class SignUpToken extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // return parent::toArray($request);

        $token = new SignUpTokenModel;
        $token->id = $this->id;
        $token->token = $this->token;

        #$encrypted_token = Crypt::encryptString($token);
        $encrypted_token = encrypt($token->toJson(JSON_PRETTY_PRINT));

        return $encrypted_token;
    }
}
