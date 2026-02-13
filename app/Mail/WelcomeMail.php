<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $lang;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $lang = 'sd')
    {
        $this->user = $user;
        $this->lang = $lang;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = ($this->lang === 'sd')
            ? 'باک ۾ ڀليڪار - سنڌ جي عظيم شاعريءَ جي لهر'
            : 'Welcome to Baakh - A Wave of Great Sindhi Poetry';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
            with: [
                'name' => $this->user->name,
                'username' => $this->user->username,
                'lang' => $this->lang,
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
