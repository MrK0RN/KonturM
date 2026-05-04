<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\Order;

/**
 * Текст/HTML письма клиенту после оформления заявки (стилизация под витрину Контур-М).
 */
final class CustomerOrderConfirmationMailBuilder
{
    /**
     * @param array<string, string> $contacts из SiteContactsService::getContacts()
     *
     * @return array{subject: string, html: string, text: string}
     */
    public function build(Order $order, array $contacts): array
    {
        $subject = sprintf('Заявка %s принята — Контур-М', $order->getOrderNumber());
        $text = $this->buildPlainText($order, $contacts);
        $html = $this->buildHtml($order, $contacts);

        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
        ];
    }

    /**
     * @param array<string, string> $contacts
     */
    private function buildPlainText(Order $order, array $contacts): string
    {
        $lines = [
            sprintf('Здравствуйте, %s!', trim($order->getCustomerName()) ?: 'клиент'),
            '',
            'Благодарим за обращение в компанию «Контур-М».',
            sprintf('Мы приняли вашу заявку %s. Менеджер свяжется с вами в течение 15 минут в рабочее время.', $order->getOrderNumber()),
            '',
            '— Ваши данные —',
            sprintf('Имя: %s', $order->getCustomerName()),
            sprintf('Телефон: %s', $order->getCustomerPhone()),
            sprintf('E-mail: %s', $order->getCustomerEmail()),
        ];

        $company = $order->getCustomerCompany();
        if ($company !== null && trim($company) !== '') {
            $lines[] = sprintf('Компания: %s', $company);
        }
        $inn = $order->getCustomerInn();
        if ($inn !== null && trim($inn) !== '') {
            $lines[] = sprintf('ИНН: %s', $inn);
        }
        $comment = $order->getComment();
        if ($comment !== null && trim($comment) !== '') {
            $lines[] = sprintf('Комментарий: %s', str_replace(["\r\n", "\r", "\n"], ' ', trim($comment)));
        }

        $lines[] = '';
        $lines[] = '— Состав заявки —';
        foreach ($order->getItems() as $i => $row) {
            $item = $this->normalizeItem($row);
            $lines[] = sprintf(
                '%d. %s',
                $i + 1,
                $item['label'],
            );
            $lines[] = sprintf('   Артикул: %s | Количество: %s | Цена: %s | Сумма: %s',
                $item['article'],
                $item['qty'],
                $item['priceLabel'],
                $item['lineTotalLabel'],
            );
        }

        $total = $order->getTotalAmount();
        if ($total !== null && $total !== '') {
            $lines[] = sprintf('Итого: %s ₽', $this->formatMoneyPlain($total));
        }

        $attachments = $order->getAttachments();
        if (is_array($attachments) && $attachments !== []) {
            $lines[] = '';
            $lines[] = 'Прикреплённые файлы:';
            foreach ($attachments as $url) {
                if (is_string($url) && $url !== '') {
                    $lines[] = '- ' . $url;
                }
            }
        }

        $lines[] = '';
        $lines[] = '— Как с нами связаться —';
        $lines[] = sprintf('Телефон: %s', $contacts['phone_main_label']);
        $lines[] = sprintf('Телефон: %s', $contacts['phone_extra_label']);
        $lines[] = sprintf('Отдел продаж: %s', $contacts['email_sales']);
        $lines[] = sprintf('Метрология: %s', $contacts['email_metrology']);
        $lines[] = sprintf('ВКонтакте: %s', $contacts['messenger_vk']);
        $lines[] = sprintf('Telegram: %s', $contacts['messenger_telegram']);
        $lines[] = sprintf('WhatsApp: %s', $contacts['messenger_whatsapp']);
        if (isset($contacts['messenger_max']) && $contacts['messenger_max'] !== '') {
            $lines[] = sprintf('MAX: %s', $contacts['messenger_max']);
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, string> $contacts
     */
    private function buildHtml(Order $order, array $contacts): string
    {
        $orangeStart = '#de6814';
        $orangeEnd = '#cc4a0a';
        $bgOuter = '#f0eeec';
        $bgCard = '#fdfdfd';
        $text = '#1a1c1f';
        $muted = '#5e6266';
        $border = '#eaeaeb';

        $nameSafe = htmlspecialchars(trim($order->getCustomerName()) ?: 'Клиент', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $numberSafe = htmlspecialchars($order->getOrderNumber(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $rowsHtml = '';
        foreach ($order->getItems() as $row) {
            $item = $this->normalizeItem($row);
            $rowsHtml .= sprintf(
                '<tr>
              <td style="padding:12px 10px;border-bottom:1px solid %7$s;color:%5$s;font:15px/1.45 Arial,Helvetica,sans-serif;">%1$s</td>
              <td style="padding:12px 10px;border-bottom:1px solid %7$s;color:%6$s;font:14px/1.45 Arial,Helvetica,sans-serif;white-space:nowrap;">%2$s</td>
              <td style="padding:12px 10px;border-bottom:1px solid %7$s;color:%6$s;font:14px/1.45 Arial,Helvetica,sans-serif;text-align:right;white-space:nowrap;">%3$s</td>
              <td style="padding:12px 10px;border-bottom:1px solid %7$s;color:%6$s;font:14px/1.45 Arial,Helvetica,sans-serif;text-align:right;white-space:nowrap;">%4$s</td>
              <td style="padding:12px 10px;border-bottom:1px solid %7$s;color:%6$s;font:14px/1.45 Arial,Helvetica,sans-serif;text-align:right;white-space:nowrap;font-weight:600;">%8$s</td>
            </tr>',
                htmlspecialchars($item['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($item['article'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($item['qty'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($item['priceLabel'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($muted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($border, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($item['lineTotalLabel'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        $totalHtml = '';
        $totalAmt = $order->getTotalAmount();
        if ($totalAmt !== null && $totalAmt !== '') {
            $totalFormatted = htmlspecialchars($this->formatMoneyPlain($totalAmt), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $totalHtml = '<p style="margin:16px 0 0;font:16px/1.45 Arial,Helvetica,sans-serif;color:' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';text-align:right;"><strong>Итого:&nbsp;' . $totalFormatted . '&nbsp;₽</strong></p>';
        }

        $dataRows = $this->customerDataRows($order);
        $dataHtml = '';
        foreach ($dataRows as [$k, $v]) {
            $dataHtml .= '<tr>'
                . '<td style="padding:6px 0;font:14px/1.5 Arial,Helvetica,sans-serif;color:' . htmlspecialchars($muted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';width:140px;vertical-align:top;">'
                . htmlspecialchars($k, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</td>'
                . '<td style="padding:6px 0;font:14px/1.5 Arial,Helvetica,sans-serif;color:' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';vertical-align:top;">'
                . $v
                . '</td>'
                . '</tr>';
        }

        $attachmentsHtml = $this->attachmentsSection($order, $muted, $text);
        $footerLinks = $this->footerContactsHtml($contacts);

        return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Заявка принята</title>
</head>
<body style="margin:0;padding:0;background:{$this->attr($bgOuter)};">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:{$this->attr($bgOuter)};padding:24px 12px;">
  <tr>
    <td align="center">
      <table role="presentation" width="100%" style="max-width:600px;border-collapse:separate;background:{$this->attr($bgCard)};border-radius:20px;overflow:hidden;box-shadow:0 12px 40px rgba(0,0,0,0.06);border:1px solid {$this->attr($border)};">
        <tr>
          <td style="padding:28px 28px 22px;background:linear-gradient(106deg,$orangeStart 6.5%,$orangeEnd 93.5%);">
            <p style="margin:0;font:700 22px/1.2 Arial,Helvetica,sans-serif;color:#ffffff;letter-spacing:0.02em;">Контур-М</p>
            <p style="margin:8px 0 0;font:14px/1.45 Arial,Helvetica,sans-serif;color:rgba(255,255,255,0.92);">Промышленные мерные приборы</p>
          </td>
        </tr>
        <tr>
          <td style="padding:28px 28px 8px;">
            <p style="margin:0 0 8px;font:18px/1.35 Arial,Helvetica,sans-serif;color:{$this->attr($text)};">Здравствуйте, {$nameSafe}!</p>
            <p style="margin:0 0 14px;font:15px/1.55 Arial,Helvetica,sans-serif;color:{$this->attr($text)};">
              Спасибо за вашу заявку. Мы уже получили запрос по номеру
              <strong style="color:{$orangeStart};">{$numberSafe}</strong> и приступили к его обработке.
            </p>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:14px 0 20px;border-radius:14px;background:#f7f6f5;border:1px solid {$this->attr($border)};">
              <tr>
                <td style="padding:16px 18px;">
                  <p style="margin:0;font:700 14px/1.4 Arial,Helvetica,sans-serif;color:{$orangeStart};text-transform:uppercase;letter-spacing:0.04em;">Что дальше</p>
                  <p style="margin:8px 0 0;font:14px/1.55 Arial,Helvetica,sans-serif;color:{$this->attr($muted)};">
                    Специалист свяжется с вами <strong style="color:{$this->attr($text)};">в&nbsp;течение 15&nbsp;минут</strong>
                    в рабочее время, чтобы подтвердить детали и ответить на вопросы.
                  </p>
                </td>
              </tr>
            </table>
            <p style="margin:22px 0 10px;font:700 16px/1.3 Arial,Helvetica,sans-serif;color:{$this->attr($text)};">Данные заявки</p>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
              {$dataHtml}
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:0 28px 28px;">
            <p style="margin:18px 0 12px;font:700 16px/1.3 Arial,Helvetica,sans-serif;color:{$this->attr($text)};">Заказ</p>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;border-radius:14px;border:1px solid {$this->attr($border)};overflow:hidden;">
              <thead>
                <tr>
                  <th align="left" style="padding:12px 10px;background:#f7f6f5;font:12px/1.25 Arial,Helvetica,sans-serif;color:{$this->attr($muted)};text-transform:uppercase;letter-spacing:0.04em;">Наименование</th>
                  <th align="left" style="padding:12px 10px;background:#f7f6f5;font:12px/1.25 Arial,Helvetica,sans-serif;color:{$this->attr($muted)};text-transform:uppercase;letter-spacing:0.04em;">Артикул</th>
                  <th align="right" style="padding:12px 10px;background:#f7f6f5;font:12px/1.25 Arial,Helvetica,sans-serif;color:{$this->attr($muted)};text-transform:uppercase;letter-spacing:0.04em;">Кол-во</th>
                  <th align="right" style="padding:12px 10px;background:#f7f6f5;font:12px/1.25 Arial,Helvetica,sans-serif;color:{$this->attr($muted)};text-transform:uppercase;letter-spacing:0.04em;">Цена</th>
                  <th align="right" style="padding:12px 10px;background:#f7f6f5;font:12px/1.25 Arial,Helvetica,sans-serif;color:{$this->attr($muted)};text-transform:uppercase;letter-spacing:0.04em;">Сумма</th>
                </tr>
              </thead>
              <tbody>
                {$rowsHtml}
              </tbody>
            </table>
            {$totalHtml}
            {$attachmentsHtml}
            <hr style="border:none;border-top:1px solid {$this->attr($border)};margin:28px 0 22px;" />
            <p style="margin:0 0 12px;font:700 16px/1.3 Arial,Helvetica,sans-serif;color:{$this->attr($text)};">Наши контакты</p>
            <p style="margin:0 0 14px;font:14px/1.55 Arial,Helvetica,sans-serif;color:{$this->attr($muted)};">
              Вы можете написать или позвонить нам напрямую — мы всегда рады помочь с&nbsp;подбором оборудования и оформлением заказа.
            </p>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
              <tr>
                <td style="font:14px/1.65 Arial,Helvetica,sans-serif;color:{$this->attr($text)};">
                  {$footerLinks}
                </td>
              </tr>
            </table>
            <p style="margin:24px 0 0;font:12px/1.45 Arial,Helvetica,sans-serif;color:{$this->attr($muted)};">
              Это письмо сформировано автоматически по&nbsp;поводу отправленной вами заявки на сайте. Отвечать на него необязательно.
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function customerDataRows(Order $order): array
    {
        $lines = [];
        $lines[] = ['Имя', htmlspecialchars($order->getCustomerName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')];
        $lines[] = ['Телефон', htmlspecialchars($order->getCustomerPhone(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')];
        $lines[] = ['E-mail', $this->mailToLink((string) $order->getCustomerEmail())];
        $company = $order->getCustomerCompany();
        if ($company !== null && trim($company) !== '') {
            $lines[] = ['Компания', htmlspecialchars($company, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')];
        }
        $inn = $order->getCustomerInn();
        if ($inn !== null && trim($inn) !== '') {
            $lines[] = ['ИНН', htmlspecialchars($inn, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')];
        }
        $comment = $order->getComment();
        if ($comment !== null && trim($comment) !== '') {
            $lines[] = ['Комментарий', nl2br(htmlspecialchars($comment, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))];
        }

        return $lines;
    }

    /**
     * @param mixed $item
     *
     * @return array{
     *   label: string,
     *   article: string,
     *   qty: string,
     *   priceLabel: string,
     *   lineTotalLabel: string,
     * }
     */
    private function normalizeItem(mixed $item): array
    {
        $row = \is_array($item) ? $item : [];
        $name = isset($row['name']) ? trim((string) $row['name']) : '';
        if ($name === '') {
            $type = isset($row['type']) ? (string) $row['type'] : 'позиция';
            $id = isset($row['id']) ? (string) $row['id'] : '?';
            $name = '(' . $type . ' ' . $id . ')';
        }
        $article = isset($row['article']) && trim((string) $row['article']) !== '' ? trim((string) $row['article']) : '—';
        $qty = isset($row['quantity']) ? max(0, (int) $row['quantity']) : 0;

        $priceNum = isset($row['price']) && $row['price'] !== null && $row['price'] !== '' ? (float) $row['price'] : null;
        $priceLabel = $priceNum !== null ? $this->formatMoneyPlain(sprintf('%.2F', $priceNum)) . ' ₽' : 'по запросу';

        $lineTotalLabel = $priceNum !== null
            ? $this->formatMoneyPlain(sprintf('%.2F', $priceNum * $qty)) . ' ₽'
            : '—';

        return [
            'label' => $name,
            'article' => $article,
            'qty' => (string) $qty,
            'priceLabel' => $priceLabel,
            'lineTotalLabel' => $lineTotalLabel,
        ];
    }

    /**
     * @param array<string, string> $contacts
     */
    private function footerContactsHtml(array $contacts): string
    {
        $phone1 = htmlspecialchars($contacts['phone_main_label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $phone1Href = htmlspecialchars($contacts['phone_main_href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $phone2 = htmlspecialchars($contacts['phone_extra_label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $phone2Href = htmlspecialchars($contacts['phone_extra_href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $emSales = htmlspecialchars($contacts['email_sales'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $emMet = htmlspecialchars($contacts['email_metrology'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $vk = htmlspecialchars($contacts['messenger_vk'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $tg = htmlspecialchars($contacts['messenger_telegram'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $wa = htmlspecialchars($contacts['messenger_whatsapp'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $maxHref = isset($contacts['messenger_max']) ? htmlspecialchars($contacts['messenger_max'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
        $maxBlock = $maxHref !== ''
            ? '&nbsp;·&nbsp;<a href="' . $maxHref . '" style="color:#5e6266;text-decoration:underline;">MAX</a>'
            : '';

        return <<<HTML
<strong>Телефоны</strong><br />
<a href="{$phone1Href}" style="color:#de6814;text-decoration:none;">{$phone1}</a><br />
<a href="{$phone2Href}" style="color:#de6814;text-decoration:none;">{$phone2}</a><br />
<br />
<strong>E-mail</strong><br />
Отдел продаж: <a href="mailto:{$emSales}" style="color:#de6814;text-decoration:none;">{$emSales}</a><br />
Метрология: <a href="mailto:{$emMet}" style="color:#de6814;text-decoration:none;">{$emMet}</a><br />
<br />
<strong>Мессенджеры</strong><br />
<a href="{$vk}" style="color:#5e6266;text-decoration:underline;">ВКонтакте</a>
&nbsp;·&nbsp;
<a href="{$tg}" style="color:#5e6266;text-decoration:underline;">Telegram</a>
&nbsp;·&nbsp;
<a href="{$wa}" style="color:#5e6266;text-decoration:underline;">WhatsApp</a>{$maxBlock}
HTML;
    }

    private function attachmentsSection(Order $order, string $muted, string $text): string
    {
        $attachments = $order->getAttachments();
        if (! \is_array($attachments) || $attachments === []) {
            return '';
        }

        $list = '';
        foreach ($attachments as $url) {
            if (\is_string($url) && $url !== '') {
                $u = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $list .= '<li style="margin:4px 0;font:14px/1.5 Arial,Helvetica,sans-serif;"><a href="' . $u . '" style="color:#de6814;text-decoration:none;word-break:break-all;">' . $u . '</a></li>';
            }
        }
        if ($list === '') {
            return '';
        }

        return '<p style="margin:20px 0 8px;font:700 15px/1.3 Arial,Helvetica,sans-serif;color:' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">Прикреплённые файлы</p>'
            . '<ul style="margin:0;padding-left:20px;color:' . htmlspecialchars($muted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';">' . $list . '</ul>';
    }

    private function mailToLink(string $email): string
    {
        $escaped = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<a href="mailto:' . $escaped . '" style="color:#de6814;text-decoration:none;">' . $escaped . '</a>';
    }

    private function attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function formatMoneyPlain(string $amount): string
    {
        return number_format((float) $amount, 2, ',', "\u{00a0}");
    }
}
