<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - Myrefell</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cinzel:400,700&family=inter:400,500,600" rel="stylesheet">
</head>
<body style="margin: 0; padding: 0; background-color: #0a0a0b; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0a0a0b; min-height: 100vh;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <!-- Main Container -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px;">
                    <!-- Header with Logo -->
                    <tr>
                        <td align="center" style="padding-bottom: 32px;">
                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="padding-right: 12px;">
                                        <!-- Crown Icon SVG -->
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#c9a227" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/>
                                            <path d="M5 21h14"/>
                                        </svg>
                                    </td>
                                    <td>
                                        <span style="font-family: 'Cinzel', serif; font-size: 28px; font-weight: 700; color: #c9a227; letter-spacing: 2px;">MYREFELL</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Card -->
                    <tr>
                        <td>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #18181b; border: 1px solid #27272a; border-radius: 12px; overflow: hidden;">
                                <!-- Gold accent line at top -->
                                <tr>
                                    <td style="height: 4px; background: linear-gradient(90deg, transparent, #c9a227, transparent);"></td>
                                </tr>

                                <!-- Content -->
                                <tr>
                                    <td style="padding: 48px 40px;">
                                        <!-- Greeting -->
                                        <h1 style="margin: 0 0 8px 0; font-family: 'Cinzel', serif; font-size: 24px; font-weight: 700; color: #fafafa; text-align: center;">
                                            Welcome, {{ $username }}
                                        </h1>
                                        <p style="margin: 0 0 32px 0; font-size: 16px; color: #a1a1aa; text-align: center;">
                                            Your journey in the realm awaits
                                        </p>

                                        <!-- Divider -->
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 32px;">
                                            <tr>
                                                <td style="height: 1px; background: linear-gradient(90deg, transparent, #27272a, transparent);"></td>
                                            </tr>
                                        </table>

                                        <!-- Message -->
                                        <p style="margin: 0 0 24px 0; font-size: 15px; line-height: 1.7; color: #d4d4d8;">
                                            Before you can claim your birthright and begin your rise from peasant to king, you must verify your email address.
                                        </p>

                                        <p style="margin: 0 0 32px 0; font-size: 15px; line-height: 1.7; color: #d4d4d8;">
                                            Click the button below to confirm your identity and enter the world of Myrefell.
                                        </p>

                                        <!-- CTA Button -->
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td align="center" style="padding: 8px 0 32px 0;">
                                                    <a href="{{ $url }}" style="display: inline-block; padding: 16px 40px; background-color: #c9a227; color: #0a0a0b; font-family: 'Cinzel', serif; font-size: 14px; font-weight: 700; text-decoration: none; border-radius: 8px; letter-spacing: 1px; text-transform: uppercase;">
                                                        Verify & Enter the Realm
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Divider -->
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 24px;">
                                            <tr>
                                                <td style="height: 1px; background: linear-gradient(90deg, transparent, #27272a, transparent);"></td>
                                            </tr>
                                        </table>

                                        <!-- Fallback Link -->
                                        <p style="margin: 0 0 12px 0; font-size: 13px; color: #71717a; text-align: center;">
                                            If the button doesn't work, copy and paste this link:
                                        </p>
                                        <p style="margin: 0; font-size: 12px; color: #c9a227; word-break: break-all; text-align: center;">
                                            {{ $url }}
                                        </p>
                                    </td>
                                </tr>

                                <!-- Gold accent line at bottom -->
                                <tr>
                                    <td style="height: 4px; background: linear-gradient(90deg, transparent, #c9a227, transparent);"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding-top: 32px;">
                            <!-- Decorative element -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="width: 40px; height: 1px; background: linear-gradient(90deg, transparent, #27272a);"></td>
                                                <td style="padding: 0 12px;">
                                                    <div style="width: 6px; height: 6px; background-color: #c9a227; transform: rotate(45deg);"></div>
                                                </td>
                                                <td style="width: 40px; height: 1px; background: linear-gradient(90deg, #27272a, transparent);"></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 8px 0; font-size: 13px; color: #71717a; text-align: center;">
                                This link will expire in 60 minutes.
                            </p>
                            <p style="margin: 0 0 16px 0; font-size: 13px; color: #52525b; text-align: center;">
                                If you did not create an account, no action is required.
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #3f3f46; text-align: center;">
                                &copy; {{ date('Y') }} Myrefell. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
