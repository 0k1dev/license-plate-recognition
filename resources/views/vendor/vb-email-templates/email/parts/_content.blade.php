<!-- CONTENT BLOCK -->
<tr>
    <td align="center" style="padding: 0 16px;">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
            <tr>
                <td bgcolor="{{config('email-templates.content_bg_color')}}"
                    align="left"
                    class="content-padding"
                    style="padding: 40px 40px 48px 40px; color: {{config('email-templates.body_color')}}; font-family: 'Inter', Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 400; line-height: 1.7; background-color: {{config('email-templates.content_bg_color')}};">

                    {!! $data['content'] ?? '' !!}

                </td>
            </tr>
        </table>
    </td>
</tr>