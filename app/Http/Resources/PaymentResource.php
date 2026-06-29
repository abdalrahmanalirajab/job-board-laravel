<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'amount'                   => $this->amount,
            'currency'                 => $this->currency,
            'provider'                 => $this->provider,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'status'                   => $this->status,
            'paid_at'                  => $this->paid_at?->format('Y-m-d H:i:s'),
            'created_at'               => $this->created_at,
            'application'              => $this->whenLoaded('application', function () {
                $application = $this->application;
                $jobListing  = $application->relationLoaded('jobListing') ? $application->jobListing : null;
                $candidate   = $application->relationLoaded('candidate') ? $application->candidate : null;

                return [
                    'id'            => $application->id,
                    'status'        => $application->status,
                    'contact_email' => $application->contact_email,
                    'job'           => $jobListing ? [
                        'id'       => $jobListing->id,
                        'title'    => $jobListing->title,
                        'location' => $jobListing->location,
                    ] : null,
                    'candidate'     => $candidate ? [
                        'id'    => $candidate->id,
                        'name'  => $candidate->name,
                        'email' => $candidate->email,
                    ] : null,
                ];
            }),
        ];
    }
}
