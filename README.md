# Shopware 6 Dutch transactional email templates

Since I couldn't find translations anywhere, here's a community project. If you find any typo's, please create a PR :)

## Import templates
A console command is available to import the templates into your database. *Be aware that this overwrites your existing mail templates!*

```
$ bin/console elgentos-dutch-email-templates:import
Succesfully upserted mail template translation for contact_form.
Succesfully upserted mail template translation for customer.group.registration.accepted.
Succesfully upserted mail template translation for customer.group.registration.declined.
Succesfully upserted mail template translation for customer.recovery.request.
Succesfully upserted mail template translation for customer_group_change_accept.
Succesfully upserted mail template translation for customer_group_change_reject.
Succesfully upserted mail template translation for customer_register.
Succesfully upserted mail template translation for customer_register.double_opt_in.
Succesfully upserted mail template translation for guest_order.double_opt_in.
Succesfully upserted mail template translation for newsletterDoubleOptIn.
Succesfully upserted mail template translation for newsletterRegister.
Succesfully upserted mail template translation for order.state.cancelled.
Succesfully upserted mail template translation for order.state.completed.
Succesfully upserted mail template translation for order.state.in_progress.
Succesfully upserted mail template translation for order.state.open.
Succesfully upserted mail template translation for order_confirmation_mail.
Succesfully upserted mail template translation for order_delivery.state.cancelled.
Succesfully upserted mail template translation for order_delivery.state.returned.
Succesfully upserted mail template translation for order_delivery.state.returned_partially.
Succesfully upserted mail template translation for order_delivery.state.shipped.
Succesfully upserted mail template translation for order_delivery.state.shipped_partially.
Succesfully upserted mail template translation for order_transaction.state.cancelled.
Succesfully upserted mail template translation for order_transaction.state.open.
Succesfully upserted mail template translation for order_transaction.state.paid.
Succesfully upserted mail template translation for order_transaction.state.paid_partially.
Succesfully upserted mail template translation for order_transaction.state.refunded.
Succesfully upserted mail template translation for order_transaction.state.refunded_partially.
Succesfully upserted mail template translation for order_transaction.state.reminded.
Succesfully upserted mail template translation for password_change.
Succesfully upserted mail template translation for product_stock_warning.
Succesfully upserted mail template translation for sepa_confirmation.
Succesfully upserted mail template translation for user.recovery.request.
```

Huge thanks to @MelvinAchterhuis for providing a large number of these :)
