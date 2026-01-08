<?php

namespace App\Mail;

use App\Models\TemporaryTeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for team invitation emails.
 */
class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public TemporaryTeamInvitation $invitation
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $raceName = $this->invitation->registration->race->race_name ?? 'Course';

        return new Envelope(
            subject: "Invitation à rejoindre une équipe - {$raceName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.team-invitation',
            with: [
                'invitation' => $this->invitation,
                'race' => $this->invitation->registration->race,
                'inviter' => $this->invitation->inviter,
                'acceptUrl' => route('invitation.show', $this->invitation->token),
                'expiresAt' => $this->invitation->expires_at->format('d/m/Y à H:i'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
