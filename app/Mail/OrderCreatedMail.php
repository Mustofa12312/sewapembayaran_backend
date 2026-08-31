<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public \App\Models\Order $order;

    public function __construct(\App\Models\Order $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Menunggu Pembayaran: Pesanan ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml()
        );
    }

    private function buildHtml(): string
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $checkoutUrl = rtrim($frontendUrl, '/') . '/order/' . $this->order->secure_token;
        $price = number_format($this->order->snapshot_price, 0, ',', '.');
        
        return "
        <div style='font-family: sans-serif; max-w: 600px; margin: 0 auto; color: #333;'>
            <h2>Halo {$this->order->customer_name},</h2>
            <p>Terima kasih atas pesanan Anda. Kami telah menerima pesanan dengan ID <strong>{$this->order->order_number}</strong>.</p>
            <p>Pesanan Anda saat ini berstatus <strong style='color:#f59e0b'>Menunggu Pembayaran</strong>.</p>
            <p>Total tagihan: <strong>Rp {$price}</strong></p>
            <p>Silakan klik tautan di bawah ini untuk melanjutkan ke proses pembayaran atau melihat detail tagihan Anda:</p>
            <p><a href='{$checkoutUrl}' style='display: inline-block; padding: 10px 20px; background-color: #2563eb; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;'>Bayar Sekarang</a></p>
            <p style='margin-top: 30px; font-size: 12px; color: #777;'>Jika Anda tidak melakukan pemesanan ini, silakan abaikan email ini.</p>
        </div>";
    }

    public function attachments(): array
    {
        return [];
    }
}
