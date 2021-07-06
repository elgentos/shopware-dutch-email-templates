# Shopware 6 Dutch transactional email templates

Since I couldn't find translations anywhere, here's a community project. If you find any typo's, please create a PR :)

## Import templates
A console command is available to import the templates into your database. *Be aware that this overwrites your existing mail templates!*

```
$ bin/console elgentos-dutch-email-templates:import
```

## Email header & footer templates

Header:

```
<div style="background:#f6f6f6;font-family:Helvetica;font-size:13px;margin:0;padding:0">
    <div style="background:#f6f6f6;font-family:Helvetica;font-size:13px;margin:0;padding:0">
        <table cellspacing="0" cellpadding="0" border="0" width="100%">
            <tbody>
                <tr>
                    <td align="center" valign="top" style="padding:20px 0 20px 0">
                       <table bgcolor="FFFFFF" cellspacing="0" cellpadding="10" border="0" width="650" style="border:1px solid #e0e0e0">
                            <tbody>
                                <tr>
                                    <td valign="top">
                                        <a href="https://your-domain.com/" target="_blank"><img src="https://absolute-path-to-your.com/logo.svg" alt="{{ salesChannel.name }}" style="margin-bottom:10px" border="0" /></a>
                                    </td>
                                </tr>
```

Footer:

```
                                <tr>
                                    <td bgcolor="#EAEAEA" align="center" style="background:#eaeaea;text-align:center">
                                        <center>
                                            <p style="font-size:12px;margin:0">Met vriendelijke groet, <strong>{{ salesChannel.name }}</strong></p>
                                        </center>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

Huge thanks to @MelvinAchterhuis for providing a large number of these :)
