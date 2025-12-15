<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Volunteer Request Received</title>
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Poppins:wght@400;700&display=swap" rel="stylesheet">
</head>
<body style="font-family: Arial, sans-serif; color: #1f1f1f; background-color: #f6f7fb; padding: 0; margin: 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f6f7fb; padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 6px 20px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background-color: #F97316; padding: 16px 24px;">
                            {{-- <h2 style="margin: 0; color: #ffffff; font-size: 20px;">Kalinga ng Kababaihan</h2> --}}
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: #fff;">
                                <div style="font-size: 0.875rem; font-weight: 700;">
                                    <p style="font-size: 0.75rem; margin: 0; font-family: Chewy, cursive;">
                                        Kalinga ng Kababaihan
                                    </p>
                                    <p style="font-size: 10px; margin: 0; font-family: Poppins, sans-serif; font-weight: 400;">
                                        Women's League Las Piñas
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px;">
                            <p style="margin: 0 0 16px 0;">Good day {{ $first_name }},</p>
                            <p style="margin: 0 0 16px 0;">
                                Thank you for your interest in volunteering for the project “{{ $project_name }}”.
                            </p>
                            <p style="margin: 0 0 16px 0;">
                                We have successfully received your volunteer request. Our team will review your application and contact you once the review is complete.
                            </p>
                            <p style="margin: 0 0 16px 0;">
                                Please note that submitting a request does not automatically confirm participation. You will receive another email once your application has been approved, including further details about the project.
                            </p>
                            <p style="margin: 0 0 16px 0;">
                                Thank you for your willingness to support the programs of Kalinga ng Kababaihan.
                            </p>
                            <p style="margin: 0;">Warm regards,<br>Kalinga ng Kababaihan Team</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f1f3f7; padding: 16px 24px; color: #4a4a4a; font-size: 12px;">
                            <p style="margin: 0;">You’re receiving this email because you submitted a volunteer request. If this wasn’t you, please ignore this message.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
