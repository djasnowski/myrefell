<?php

use App\Models\PlayerMail;
use App\Models\User;
use App\Services\MailService;

test('sending mail costs 5g', function () {
    $sender = User::factory()->create(['gold' => 500]);
    $recipient = User::factory()->create();

    $service = app(MailService::class);
    $result = $service->sendMail($sender, $recipient, 'Hello', 'Test message');

    expect($result['success'])->toBeTrue();
    expect($result['mail']->gold_cost)->toBe(5);
    expect($result['mail']->is_carrier_pigeon)->toBeTrue();
    expect($sender->fresh()->gold)->toBe(495);
});

test('cannot send mail without enough gold', function () {
    $sender = User::factory()->create(['gold' => 3]);
    $recipient = User::factory()->create();

    $service = app(MailService::class);
    $result = $service->sendMail($sender, $recipient, 'Hello', 'Test message');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('5g');
    expect($sender->fresh()->gold)->toBe(3);
    expect(PlayerMail::count())->toBe(0);
});

test('cannot send mail to self', function () {
    $sender = User::factory()->create();

    $service = app(MailService::class);
    $result = $service->sendMail($sender, $sender, 'Hello', 'Test message');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('yourself');
    expect(PlayerMail::count())->toBe(0);
});

test('reading mail marks it as read', function () {
    $mail = PlayerMail::factory()->create(['is_read' => false]);

    $service = app(MailService::class);
    $recipient = User::find($mail->recipient_id);
    $result = $service->readMail($recipient, $mail);

    expect($result)->not->toBeNull();
    expect($mail->fresh()->is_read)->toBeTrue();
    expect($mail->fresh()->read_at)->not->toBeNull();
});

test('reading mail as sender does not mark it as read', function () {
    $mail = PlayerMail::factory()->create(['is_read' => false]);

    $service = app(MailService::class);
    $sender = User::find($mail->sender_id);
    $result = $service->readMail($sender, $mail);

    expect($result)->not->toBeNull();
    expect($mail->fresh()->is_read)->toBeFalse();
});

test('unauthorized user cannot read mail', function () {
    $mail = PlayerMail::factory()->create();
    $stranger = User::factory()->create();

    $service = app(MailService::class);
    $result = $service->readMail($stranger, $mail);

    expect($result)->toBeNull();
});

test('deleting from inbox does not affect sender sent view', function () {
    $mail = PlayerMail::factory()->create();

    $service = app(MailService::class);
    $recipient = User::find($mail->recipient_id);
    $service->deleteMail($recipient, $mail);

    $fresh = $mail->fresh();
    expect($fresh->is_deleted_by_recipient)->toBeTrue();
    expect($fresh->is_deleted_by_sender)->toBeFalse();
});

test('deleting from sent does not affect recipient inbox', function () {
    $mail = PlayerMail::factory()->create();

    $service = app(MailService::class);
    $sender = User::find($mail->sender_id);
    $service->deleteMail($sender, $mail);

    $fresh = $mail->fresh();
    expect($fresh->is_deleted_by_sender)->toBeTrue();
    expect($fresh->is_deleted_by_recipient)->toBeFalse();
});

test('unauthorized user cannot delete mail', function () {
    $mail = PlayerMail::factory()->create();
    $stranger = User::factory()->create();

    $service = app(MailService::class);
    $result = $service->deleteMail($stranger, $mail);

    expect($result)->toBeFalse();
});

test('unread count is accurate', function () {
    $user = User::factory()->create();

    // Create 3 unread mails
    PlayerMail::factory()->count(3)->create(['recipient_id' => $user->id, 'is_read' => false]);

    // Create 2 read mails
    PlayerMail::factory()->count(2)->read()->create(['recipient_id' => $user->id]);

    // Create 1 deleted unread mail
    PlayerMail::factory()->create([
        'recipient_id' => $user->id,
        'is_read' => false,
        'is_deleted_by_recipient' => true,
    ]);

    $service = app(MailService::class);
    expect($service->getUnreadCount($user))->toBe(3);
});

test('inbox excludes deleted mails', function () {
    $user = User::factory()->create();

    PlayerMail::factory()->count(3)->create(['recipient_id' => $user->id]);
    PlayerMail::factory()->create([
        'recipient_id' => $user->id,
        'is_deleted_by_recipient' => true,
    ]);

    $service = app(MailService::class);
    $inbox = $service->getInbox($user);

    expect($inbox->total())->toBe(3);
});

test('sent mail excludes deleted mails', function () {
    $user = User::factory()->create();

    PlayerMail::factory()->count(2)->create(['sender_id' => $user->id]);
    PlayerMail::factory()->create([
        'sender_id' => $user->id,
        'is_deleted_by_sender' => true,
    ]);

    $service = app(MailService::class);
    $sent = $service->getSentMail($user);

    expect($sent->total())->toBe(2);
});

test('send mail endpoint validates subject and body length', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $this->actingAs($sender);

    $response = $this->post('/mail/send', [
        'recipient_username' => $recipient->username,
        'subject' => str_repeat('a', 101),
        'body' => 'test',
    ]);

    $response->assertSessionHasErrors('subject');

    $response2 = $this->post('/mail/send', [
        'recipient_username' => $recipient->username,
        'subject' => 'test',
        'body' => str_repeat('a', 1001),
    ]);

    $response2->assertSessionHasErrors('body');
});

test('send mail endpoint validates recipient exists', function () {
    $sender = User::factory()->create();

    $this->actingAs($sender);

    $response = $this->post('/mail/send', [
        'recipient_username' => 'nonexistent_player_xyz',
        'subject' => 'Hello',
        'body' => 'Test message',
    ]);

    $response->assertSessionHasErrors('recipient_username');
});
