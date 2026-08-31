<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaidMail extends Mailable implements ShouldQueue
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
            subject: 'Pembayaran Berhasil: Pesanan ' . $this->order->order_number,
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
        $dashboardUrl = rtrim($frontendUrl, '/') . '/order/' . $this->order->secure_token;
        
        $licenseHtml = "";
        if ($this->order->licenseKeys && $this->order->licenseKeys->count() > 0) {
            $licenseHtml = "<h3>Lisensi Anda:</h3>
            <div style='background-color: #f3f4f6; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 16px; font-weight: bold; margin-bottom: 20px; border: 1px solid #10b981;'>
                " . $this->order->licenseKeys->first()->license_key . "
            </div>";
        }

        return "
        <div style='font-family: sans-serif; max-w: 600px; margin: 0 auto; color: #333;'>
            <h2>Halo {$this->order->customer_name},</h2>
            <p>Terima kasih! Pembayaran untuk pesanan <strong>{$this->order->order_number}</strong> telah kami terima.</p>
            <p>Pesanan Anda saat ini berstatus <strong style='color:#10b981'>AKTIF / DIBAYAR</strong>.</p>
            
            {$licenseHtml}

            <p>Anda dapat melihat detail struk, mengunduh invoice PDF, atau mengakses panel kontrol pelanggan melalui tautan di bawah ini:</p>
            <p><a href='{$dashboardUrl}' style='display: inline-block; padding: 10px 20px; background-color: #2563eb; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;'>Lihat Pesanan Saya</a></p>
            <p style='margin-top: 30px; font-size: 12px; color: #777;'>Terima kasih telah berbelanja bersama kami.</p>
        </div>";
    }

    public function attachments(): array
    {
        return [];
    }
}
