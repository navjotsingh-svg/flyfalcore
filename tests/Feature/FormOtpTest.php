<?php

namespace Tests\Feature;

use App\Mail\OneTimePasscodeMail;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FormOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_saves_only_after_otp_verify(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+447700900123',
            'message' => 'Please help with a group booking.',
        ])->assertRedirect(route('contact'));

        $this->assertDatabaseCount('contact_messages', 0);
        $this->assertDatabaseCount('otps', 1);

        $code = $this->capturedCode('ada@example.com');

        $this->from(route('contact'))->post('/otp/verify', [
            'purpose' => Otp::CONTACT,
            'code' => '000000',
        ])->assertRedirect(route('contact'))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('contact_messages', 0);

        $this->post('/otp/verify', [
            'purpose' => Otp::CONTACT,
            'code' => $code,
        ])->assertRedirect(route('contact'));

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+447700900123',
            'message' => 'Please help with a group booking.',
        ]);
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_newsletter_saves_only_after_otp_verify(): void
    {
        Mail::fake();

        $this->from('/')->post('/newsletter', [
            'email' => 'notes@example.com',
        ])->assertRedirect('/');

        $this->assertDatabaseCount('newsletter_subscribers', 0);

        $code = $this->capturedCode('notes@example.com');

        $this->from('/')->post('/otp/verify', [
            'purpose' => Otp::NEWSLETTER,
            'code' => $code,
        ])->assertRedirect('/');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'notes@example.com',
        ]);
        $this->assertNotNull(NewsletterSubscriber::query()->where('email', 'notes@example.com')->value('verified_at'));
    }

    public function test_wrong_signup_code_does_not_create_a_user(): void
    {
        Mail::fake();

        $this->post('/signup', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ]);

        $this->from(route('signup'))->post('/otp/verify', [
            'purpose' => Otp::SIGNUP,
            'code' => '111111',
        ])->assertRedirect(route('signup'))
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'ada@example.com']);
    }

    protected function capturedCode(string $email): string
    {
        $code = '';

        Mail::assertSent(OneTimePasscodeMail::class, function (OneTimePasscodeMail $mail) use ($email, &$code) {
            if (! $mail->hasTo($email)) {
                return false;
            }

            $code = $mail->code;

            return true;
        });

        $this->assertNotSame('', $code);

        return $code;
    }
}
