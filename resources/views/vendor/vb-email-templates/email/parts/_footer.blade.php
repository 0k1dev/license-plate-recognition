<!-- FOOTER -->
<tr>
    <td align="center" style="padding: 0 16px;">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
            <tr>
                <td bgcolor="#1e293b" align="center"
                    style="padding: 28px 40px; border-radius: 0 0 12px 12px; background-color: #1e293b;">

                    <!-- Footer Links -->
                    <p style="margin: 0 0 12px 0; font-family: 'Inter', Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 500; line-height: 1.5;">
                        @foreach(config('email-templates.links') as $link)
                        <a href="{{$link['url']}}" target="_blank"
                            title="{{$link['title']}}"
                            style="color: #94a3b8; text-decoration: none; margin: 0 8px;">{{$link['name']}}</a>
                        @if(! $loop->last)<span style="color: #475569;">|</span>@endif
                        @endforeach
                    </p>

                    <!-- Copyright -->
                    <p style="margin: 0; font-family: 'Inter', Helvetica, Arial, sans-serif; font-size: 12px; color: #64748b; line-height: 1.5;">
                        &copy; <?= date('Y'); ?> <strong style="color: #94a3b8;">{{config('app.name')}}</strong>. Tất cả quyền được bảo lưu.
                    </p>
                    <p style="margin: 8px 0 0 0; font-family: 'Inter', Helvetica, Arial, sans-serif; font-size: 12px; color: #475569; line-height: 1.5;">
                        Email này được gửi tự động, vui lòng không trả lời.
                    </p>
                </td>
            </tr>
        </table>
    </td>
</tr>

<!-- BOTTOM SPACER -->
<tr>
    <td height="30" style="font-size: 0; line-height: 0;">&nbsp;</td>
</tr>

</table>
</body>

</html>