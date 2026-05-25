<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: letter portrait;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #10233d;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            font-weight: 700;
        }

        .page {
            position: relative;
            width: 8.5in;
            height: 11in;
            overflow: hidden;
            padding: 0;
        }

        .sidebar {
            position: absolute;
            left: 0.30in;
            top: 0.36in;
            width: 2.25in;
            height: 10.25in;
        }

        .main {
            position: absolute;
            left: 2.88in;
            top: 0.26in;
            width: 5.24in;
            height: 10.12in;
        }

        .logo-box {
            width: 1.70in;
            height: 1.70in;
            margin: 0 auto 0.26in;
            background: #fb861f;
            text-align: center;
            padding-top: 0.24in;
        }

        .logo-circle {
            width: 1.12in;
            height: 1.12in;
            margin: 0 auto;
            background: #ffffff;
            border: 1.5px solid #0984a8;
            border-radius: 50%;
            padding: 0.07in;
        }

        .logo-circle img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .org-title {
            color: #1089b7;
            font-size: 14px;
            line-height: 1.22;
            font-weight: 900;
            letter-spacing: 0.2px;
            margin-bottom: 0.16in;
        }

        .sidebar p {
            margin: 0;
            line-height: 1.28;
            font-size: 8.4px;
            font-style: italic;
            font-weight: 800;
        }

        .heading {
            font-size: 13px;
            font-weight: 900;
            margin: 0.15in 0 0.08in;
            font-style: normal;
        }

        .contact-title {
            color: #35a7d5;
            margin-top: 0.22in;
        }

        .contact-label {
            margin-top: 0.09in !important;
            font-style: normal !important;
            font-weight: 900 !important;
        }

        .contact-link {
            color: #1788b9;
            text-decoration: underline;
            word-break: break-all;
        }

        .section {
            border: 1.4px solid #10233d;
            padding: 0.09in 0.10in 0.10in;
            margin-bottom: 0.10in;
            width: 100%;
            overflow: hidden;
        }

        .personal {
            height: 3.82in;
        }

        .emergency {
            height: 2.32in;
        }

        .signature {
            height: 0.84in;
            padding-top: 0.49in;
            text-align: center;
        }

        .privacy {
            height: 1.88in;
        }

        .title-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.07in;
        }

        .form-title {
            color: #f6a85f;
            font-size: 21px;
            font-weight: 400;
            letter-spacing: 0.5px;
            padding: 0.08in 0 0.39in 0.02in;
            vertical-align: top;
        }

        .picture-box {
            width: 0.98in;
            height: 0.98in;
            border: 1.3px solid #10233d;
            text-align: center;
            vertical-align: middle;
            font-size: 8.6px;
            line-height: 1.15;
            font-weight: 900;
        }

        .section-heading {
            color: #f6a85f;
            border-bottom: 1px solid #3c9bc1;
            font-size: 11px;
            font-weight: 400;
            letter-spacing: 0.2px;
            padding-bottom: 0.03in;
            margin-bottom: 0.07in;
        }

        .field {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.035in;
        }

        .field td {
            vertical-align: bottom;
        }

        .label {
            width: 1%;
            white-space: nowrap;
            font-size: 8.8px;
            font-weight: 900;
            padding-right: 0.06in;
        }

        .line {
            border-bottom: 1.2px solid #10233d;
            height: 0.18in;
            font-size: 7.9px;
            font-weight: 700;
            padding: 0 0.05in 0.02in;
        }

        .wide-line {
            height: 0.29in;
        }

        .signature-line {
            width: 82%;
            margin: 0 auto;
            border-top: 1.2px solid #10233d;
            font-size: 9.6px;
            font-weight: 900;
            padding-top: 0.04in;
        }

        .privacy-title {
            font-size: 11px;
            font-weight: 900;
            margin: 0 0 0.14in;
        }

        .privacy p {
            margin: 0 0 0.09in;
            font-size: 8.25px;
            line-height: 1.23;
            font-style: italic;
            font-weight: 800;
        }
    </style>
</head>
<body>
@php
    $fullName = trim(collect([$member->first_name, $member->middle_name, $member->last_name])->filter()->implode(' '));
    $value = fn ($text) => $text ?: '';
@endphp
<div class="page">
            <div class="sidebar">
                <div class="logo-box">
                    <div class="logo-circle">
                        <img src="{{ $logoPath }}" alt="Kalinga ng Kababaihan">
                    </div>
                </div>

                <div class="org-title">
                    KALINGA NG KABABAIHAN<br>
                    WOMEN'S LEAGUE LAS PINAS
                </div>

                <p>
                    84 LOT6-6 FANTACY ROAD 3 TERESA PARK<br>
                    SUBD. PILAR LAS PINAS CITY<br>
                    SEC REG. NO.: 2024100171937-10
                </p>

                <p style="margin-top: 0.19in;">
                    A self-sustaining non-governmental organization that aims to promote a sense of
                    community and cooperation among like-minded and self-sufficiency-seeking
                    individuals to contribute to the betterment of the society.
                </p>

                <div class="heading">VISION</div>
                <p>Empowered and united women through volunteerism towards community resiliency and development.</p>

                <div class="heading">MISSION</div>
                <p>
                    To promote and strengthen the physical and social well-being of children and senior
                    members of the community, through nutrition programs, greening and planting programs,
                    responding to emergencies and calamities.
                </p>

                <div class="heading contact-title">CONTACT</div>
                <p>
                    LL#: 0283742811<br>
                    CP#: 09209859508
                </p>
                <p class="contact-label">FB PAGE:</p>
                <p class="contact-link">https://www.facebook.com/kalingangkababaihanwllpc</p>
                <p class="contact-label">EMAIL:</p>
                <p>kalingangkababaihan.wllpc@gmail.com</p>
            </div>

            <div class="main">
                <div class="section personal">
                    <table class="title-row">
                        <tr>
                            <td class="form-title">MEMBERSHIP FORM</td>
                            <td class="picture-box">1x1<br>PICTURE</td>
                        </tr>
                    </table>

                    <div class="section-heading">PERSONAL INFORMATION</div>
                    @foreach ([
                        'FULLNAME' => $fullName,
                        'NICKNAME' => $member->nick_name,
                        'COMPLETE ADDRESS' => $member->address,
                        'BIRTHDATE' => $member->dob,
                        'CIVIL STATUS' => $member->civil_status,
                        'CONTACT NUMBER' => $member->contact_number,
                        'FACEBOOK/MESSENGER ACCOUNT' => $member->fb_messenger_account,
                    ] as $label => $fieldValue)
                        <table class="field">
                            <tr>
                                <td class="label">{{ $label }}:</td>
                                <td class="line {{ $label === 'COMPLETE ADDRESS' ? 'wide-line' : '' }}">{{ $value($fieldValue) }}</td>
                            </tr>
                        </table>
                    @endforeach
                </div>

                <div class="section emergency">
                    <div class="section-heading">EMERGENCY CONTACT INFORMATION</div>
                    @foreach ([
                        'CONTACT PERSON' => optional($emergency)->contact_person,
                        'COMPLETE ADDRESS' => optional($emergency)->address,
                        'CONTACT NUMBER' => optional($emergency)->contact_number,
                        'FB/MESSENGER ACCOUNT' => optional($emergency)->fb_messenger_account,
                        'RELATION TO THE APPLICANT' => optional($emergency)->relationship,
                    ] as $label => $fieldValue)
                        <table class="field">
                            <tr>
                                <td class="label">{{ $label }}:</td>
                                <td class="line {{ $label === 'COMPLETE ADDRESS' ? 'wide-line' : '' }}">{{ $value($fieldValue) }}</td>
                            </tr>
                        </table>
                    @endforeach
                </div>

                <div class="section signature">
                    <div class="signature-line">COMPLETE NAME AND SIGNATURE</div>
                </div>

                <div class="section privacy">
                    <div class="privacy-title">DATA PRIVACY</div>
                    <p>
                        In compliance with the R.A. 10173 or "DATA PRIVACY ACT of 2012", all the information
                        provided on this form shall be collected for specified and legitimate purposes only,
                        processed fairly and lawfully, adequate, and not excessive in relation to the purposes
                        for which they are collected and processed and treated with confidentiality.
                    </p>
                    <p>
                        By signing this form, I agree on the processing of my personal information and payment
                        of corresponding fees. I am also certifying that this membership form has been
                        accomplished by me and is true, correct and complete. I also authorize the KALINGA NG
                        KABABAIHAN WOMEN'S LEAGUE LAS PINAS or its representative to validate the contents
                        stated herein.
                    </p>
                </div>
            </div>
</div>
</body>
</html>
