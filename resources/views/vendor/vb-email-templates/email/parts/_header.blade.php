<!DOCTYPE html>
<html>

<head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <style type="text/css">
        /* FONTS */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        /* CLIENT-SPECIFIC STYLES */
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
        }

        /* RESET STYLES */
        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        table {
            border-collapse: collapse !important;
        }

        body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        a {
            color: {
                    {
                    config('email-templates.anchor_color')
                }
            }

            ;
        }

        /* iOS BLUE LINKS */
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        /* MOBILE STYLES */
        @media screen and (max-width: 600px) {
            h1 {
                font-size: 24px !important;
                line-height: 32px !important;
            }

            .container {
                width: 100% !important;
            }

            .content-padding {
                padding: 24px !important;
            }
        }

        /* ANDROID CENTER FIX */
        div[style*="margin: 16px 0;"] {
            margin: 0 !important;
        }
    </style>
</head>

<body style="background-color: #f1f5f9; margin: 0 !important; padding: 0 !important; font-family: 'Inter', Helvetica, Arial, sans-serif;">

    <!-- HIDDEN PREHEADER TEXT -->
    <div style="display: none; font-size: 1px; color: #f1f5f9; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
        {{ $data['preheader'] ?? '' }}
    </div>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9;">
        <!-- TOP SPACER -->
        <tr>
            <td height="30" style="font-size: 0; line-height: 0;">&nbsp;</td>
        </tr>

        <!-- LOGO HEADER -->
        <tr>
            <td align="center" style="padding: 0 16px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                    <tr>
                        <td bgcolor="{{config('email-templates.header_bg_color')}}" align="center"
                            style="padding: 28px 30px; border-radius: 12px 12px 0 0; background-color: {{config('email-templates.header_bg_color')}};">
                            <a href="{{\Illuminate\Support\Facades\URL::to('/')}}" target="_blank" title="{{config('app.name')}}">
                                <img alt="{{config('app.name')}} Logo"
                                    src="{{asset(config('email-templates.logo'))}}"
                                    width="{{config('email-templates.logo_width')}}"
                                    style="display: block; max-width: 180px; height: auto;" border="0">
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>