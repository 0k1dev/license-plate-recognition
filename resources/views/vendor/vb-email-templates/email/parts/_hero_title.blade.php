<!-- HERO TITLE -->
<tr>
    <td align="center" style="padding: 0 16px;">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
            <tr>
                <td bgcolor="{{config('email-templates.header_bg_color')}}"
                    align="center" valign="top"
                    style="padding: 32px 40px 36px 40px; background-color: {{config('email-templates.header_bg_color')}};">
                    <h1 style="font-size: 26px; font-weight: 700; margin: 0; color: #ffffff; font-family: 'Inter', Helvetica, Arial, sans-serif; letter-spacing: -0.025em; line-height: 1.3;">
                        {{ $data['title'] ?? '' }}
                    </h1>
                </td>
            </tr>
            <!-- Divider strip at bottom of header -->
            <tr>
                <td height="4" style="background-color: rgba(255,255,255,0.2); font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
        </table>
    </td>
</tr>