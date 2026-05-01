<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f7; }
        .container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #1e293b; color: #ffffff; padding: 24px 32px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 600; }
        .body { padding: 32px; }
        .field { margin-bottom: 20px; }
        .field-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px; }
        .field-value { font-size: 15px; color: #1f2937; line-height: 1.5; }
        .message-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-top: 8px; }
        .footer { background: #f9fafb; padding: 16px 32px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📩 New Contact Message</h1>
        </div>
        <div class="body">
            <div class="field">
                <div class="field-label">Name</div>
                <div class="field-value">{{ $contactMessage->first_name }} {{ $contactMessage->last_name }}</div>
            </div>
            <div class="field">
                <div class="field-label">Email</div>
                <div class="field-value"><a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a></div>
            </div>
            @if($contactMessage->subject)
            <div class="field">
                <div class="field-label">Subject</div>
                <div class="field-value">{{ $contactMessage->subject }}</div>
            </div>
            @endif
            <div class="field">
                <div class="field-label">Message</div>
                <div class="message-box">
                    {!! nl2br(e($contactMessage->message)) !!}
                </div>
            </div>
        </div>
        <div class="footer">
            This message was sent via your website contact form.
        </div>
    </div>
</body>
</html>
