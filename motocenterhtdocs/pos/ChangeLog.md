# CHANGELOG DoliPOS FOR <a href="https://www.dolibarr.org">DOLIBARR ERP CRM</a>

## 14.1.1
- Fix: Doesn't load product translation

## 14.1.0
- New: Compatibility with centralized management multi-company users <br/>

## 14.0.0
- New: Compatibility with Dolibarr 14.0.0
- New: Multicompany compatibility
- Fix: Does not show the warehouse when searching for product
- Fix: Cash closings are not sent by email

## 13.0.0
- New: Compatibility with Dolibarr 13.0.0
- Fix: Solve conflict between numeration of tickets and numeration of support tickets
- Fix: Line break in ticket's footer
- Fix: Correct show discounts

## 12.1.0
- Fix: By having 2Promos activated, very small decimals (0'0000001) of discount come out on the tickets even if the promotion is never being applied. Also, if a promotion of 40 is applied, it reflects 39'99999999
- Fix: When a percentage promotion is applied in 2Promos, DoliPos applies the discount twice: a first time in the list of products, and then at the time of invoicing
- Fix: Error when creating cash closings putting commas instead of periods in amounts
- Fix: VAT was not itemized on returns
- Fix: The minimum price was not respected when applying discounts  
- Fix: Refunds could not be made in restrictive PHP settings  
- New: New permission to control whether a user can create new products or not from frontend

## 12.0.1
- Fix: Stock is not subtracted from the warehouses if product batch module is active
- Fix: Terminal uses rights of the user who's logged in Dolibarr instead of rights of the users who's logged in terminal
- Fix: Can't save sells due to SQL error


## 12.0.0
- New: Compatibility with Dolibarr 12.0.0
- Fix: Filters in backend lists
- Fix: Literal langs from backend
- Fix: Discounts from 2promo
- Fix: Product's price in the frontend with IVA or not, depending on the module's configuration

## 11.0.2
- Fix: If the option of selling products without stock is activated then allow to sell all products whithout any restriction
- Fix: Error sql when filtering per total vat in the facture list of backend
- Fix: Bad division of discount value when the sell value is lower and it applied the discount

## 11.0.1
- Fix: Create a new fixed discount if not used completely not work for refunds sales

## 11.0.0
- New: Compatibility with Dolibarr 11.0.0
- New: Create a new fixed discount if not used completely
- New: Option to select in the frontend sell list the lines to refund
- New: Option to specify the qty of the line at the frontend sell
- New: Option to show number of products added in the sell
- New: Addition of user's permissions for apply discounts in frontend and for make refunds
- New: In the products menu from the bill's report from the backend can filter by all levels of subcategories
- New: Option to view all sales of all terminals from the invoice history if option of restrinct user's access by terminal is activated
- New: Possibility to finish paying unpaid sales from the frontend
- Fix: Didn't allow to add discounts with decimals in the frontend
- Fix: Massively bill only closed tickets

## 10.0.2
- Fix: The logo of the company didn't appear if the option is activate in the POS config
- Fix: If the article has a price with no TAX and has a minimal price of sell, at the frontend of the POS use that minimal price of sell

## 10.0.1
- Fix: The list of credit notes invoices don't filter per credit notes, show all the invoices in the backend
- Fix: The creation of credit notes don't work properly in the frontend

## 10.0.0
- New: Compatibility with Dolibarr 10.0.0
- New: Don't ask for email when the third send the ticket if he already has an defined email
- New: Convert tickets into invoices massively
- New: Add pagination in invoices history and products search
- New: Add checkbox in tickets list to delete them massively
- New: Add link in the frontend to access the third party file
- New: Show product reference and stock in the frontend info of the product
- New: Add the number lines of the sales in the invoices history
- New: Add a button to empty the chat since the backend of the terminal
- New: Make the notes visible in the frontend
- Fix: No products found by barcode starting with 0
- Fix: Cannot record tickets in draft or under POS_TICKET
- Fix: Not read EAN13 barcodes with weight indication if value is 0
- Fix: Search products function in frontend fails if the options of sell services and sell products without stock are activated
- Fix: It is not possible to select a draft ticket to continue with its sale
- Fix: Reload a provisional ticket fails

## 9.0.2
- New: Disable autocomplete on all inputs
- Fix: Reading EAN13 barcodes with weight indication

## 9.0.1
- Fix: MYSQL strict mode

## 9.0.0
- New: Compatibility with Dolibar 9.0.0
- New: Reading EAN13 barcodes with weight indication
- New: View the logo on the ticket
- New: Ability to hide a products category in the terminal

## 8.0.4
- Fix: Refunds it counts like positive in the backend reports of POS

## 8.0.3
- Fix: Prices and discounts don't apply when changing clients in the frontend
- Fix: Error of old constraint in tickets
- Fix: Rewards points aren't updated when save sales in the frontend
- Fix: The equivalence surcharge is not added to the total of the sale in the frontend, but yes to the discount

## 8.0.2
- New: Langs En/Fr

## 8.0.1
- New: POS Drawer
- Fix: Bug with 2Rewards compatibility 
- Fix: Bug with products with same label
- Fix: Bug when showing prices without tax

## 8.0.0
- New: Compatibility with Dolibar 8.0.0
- New: Compatibility with batch/series
- Fix: Correct search of products in frontend
- Fix: Correct quantity when making returns

## 7.0.1
- Fix: Bug when product price is on HT

## 7.0.0
- New:Compatibility with Dolibarr 7
- New:Possibility to see prices on frontend without tax
- Fix: Direct login

## 6.0.5 
- Fix: Correct on closecash

## 6.0.4
- Fix: Correct on printing
- Fix: Compatibility with ticket use

## 6.0.3
- Fix: Ticket correction
- Fix: Multicurrency compatibility
- Fix: Correct with points in draft tickets
- Fix: Bug on best sells
- Fix: Bug on user rights

## 6.0.2
- Fix: Bug in returns when stock is zero

## 6.0.1
- Fix: Too many results in the listings
- Fix: Rounding on tickets
- Fix: Price when returning

## 6.0.0
- New:Possibility of using the minimum sales price
- New:Closecash by paymode
- New:Support multilang products
- Qual: Better buyprice calculation
- New:Compatibility with MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND hidden option

## 5.0.7
- Fix: Bug using localtax

## 5.0.6
- Fix: Bug when use coupon
- Fix: Bug when return and stock is 0

## 5.0.5
- Fix: Bug on discounts returns
- Fix: Bug on list criteria

## 5.0.4
- Fix: Bug on returns with localtax
- Fix: Bug on closecash list
- Fix: Bug on tax report

## 5.0.3
- Fix: Closecash tpl correction
- Fix: PMP correction when returns
- Fix: Correction compatibility with 2Rewards

## 5.0.2
- Fix: Corrected pmp calcul
- Fix: Compatibility 2Promo
- Fix: Bug on lists
- Fix: Bug when using localtax

## 5.0.1
- Fix: Warning on stock movement
- Fix: Error with PHP 7.1
- Fix: Bug when company haven't got state

## 5.0.0
- New:Compatibility with module 2Series
- New:Asf confirmation when amount paid is lesser than total
- New:Added list with vat sales made on DoliPOS

## 4.0.5
- Fix: Bug when using discount
- Fix: Bug when updating draft sell

## 4.0.4
- Fix: Error compatibilidad multicompany
- Fix: Bug on ticket list

## 4.0.3
- Fix: Set paid a invoice when remain to pay is zero
- Fix: When product stock is zero

## 4.0.2
- Fix: Corrected VAT application according to customer's country

## 4.0.1
- Fix: Bug when no products
- Fix: Error when sql_mode=only_full_group_by
- Fix: Warning declaration for PHP 7.0

## 4.0.0
- Fix: Compatibility Dolibarr 4.0
- New:Compatibility PHP 7.0

## 3.9.5
- Fix: Bug on closecash list

## 3.9.4
- Fix: User can only connect to one terminal
- Fix: Disconnect user when terminal is liberated
- Fix: Bug when change user on frontend
- Fix: Bug when creating close cash
- Fix: Use decimals defined on Dolibarr
- Fix: Sales without stock according Dolibarr

## 3.9.3
- Fix: Check the user's permissions to access the frontend from the main login
- Fix: Check IdProf when create thirds and invoices

## 3.9.2
- Fix: Bug when showing images
- Fix: Bug not restoring all prices when restore ticket

## 3.9.1
- Fix: Bug when print ticket if place is used
- Fix: Bug when discount customer

## 3.9.0
- Fix: Bug restore ticket if product is edited

## 3.8.4
- Fix: Not possible to create products
- Fix: Bug on listecloses filter

## 3.8.3
- Fix: Bug on closecash list

## 3.8.2
- Fix: Bug with customer prices

## 3.8.1
- Fix: Bug on closecash

## 3.8.0
- New:Compatibility with new commercial discount of 2Promo module
- New:Compatibility with Dolibarr 3.8

## 3.7.3
- Fix: Not showing accountancy codes on sales journal
- Fix: Improve calculePrice

## 3.7.2
- Fix: Dolibarr 3.7 compatibility
- Fix: Bug on services stock
- Fix: Bug on multiprice
- Fix: Bug using discounts

## 3.7.1
- Fix: Bug on mobile view with https
- Fix: Bug compatibility with 2Promo
- Fix: Bug using discounts

## 3.7.0
- New:Send pdf invoices by email
- New:Show ticket's note on print ticket
- New:Option to show prices with or without taxes
- New:Only show discounts if at least one discount
- New:Close cash from backend
- New:User rights on each terminal
- New:Series by terminal or warehouse

## 3.6.4
- Fix: Sales without stock

## 3.6.3
- Fix: JZebra print
- Fix: SQL search with specials characters
- Fix: Compatibility in 'htdocs' folder

## 3.6.2
- Fix: Fullscreen Chrome bug

## 3.6.1
- Fix: Bug in module configuration
- Fix: Bug in thirds creation

## 3.6.0
- New:Show ticket reference when recover it
- New:Add company phone on printed ticket
- New:Most selled products repport
- New:Possibility to convert devolution on a discount
- New:Include Jzebra print
- New:Improve printed ticket
- New:Pay a purchase with two payment methods
- New:Option to automatically close window print ticket
- New:Add the price on the drop down product search
- New:At least one character to search products or customers
- New:Add town field when create customer
- New:Tickets available through hidden option
- New:Access POS by logging only once
- New:Show cashback on bills

## 3.5.6
- New:Compatibility with multicompany

## 3.5.5
- Fix: Bug paybank extra closecash
- Fix: DB compatibility

## 3.5.4
- Fix: Bug when searching products when sell services activated
- Fix: Bug save terminal on ticket

## 3.5.3
- Fix: Bug when session expired
- Fix: Bug showing product label on ticket

## 3.5.2
- Fix: Ticket lines not show on backend

## 3.5.1
- Fix: Bug in discount draft tickets

## 3.5.0
- New:Mobile addon included
- New:Better format on ticket
- New:No compatibility with multicompany
- New:Possibility to show inverted price

## 3.4.3
- Fix: Select company with type client 1 or 3
- Fix: Lost fields values into reload
- Fix: Payment fields empty by default
- Fix: Show ref of warehouse instead lieu
- Fix: Replace img cashdesk with img barcode 

## 3.4.2
- Fix: Bug in facture numeration with company type

## 3.4.1
- Fix: Ticket format
- Fix: Bug when cash import is zero
- Fix: Bug in returned products without stock
- Fix: Bug showing products in category

## 3.4.0
- New:Add an extra bank payment mode
- New:Added VAT breakdown
- New:Close cash numbering module
- New:Chat on frontend
- New:User change with password
- New:Improve mobile detect
- New:Change twitter frontend

## 3.3.7
- Fix: Error when print closecash
- Fix: Access to frontend in new tab
- Fix: Improve transalations
- New:Add free text on tickets

## 3.3.6
- Fix: Not shown date in factures
- Fix: Bug when not show products without stock

## 3.3.5
- Fix: Bug when create customer from frontend
- Fix: Bug when create product from frontend

## 3.3.4
- Fix: Bug in tactil mode
- Fix: Bug in 'more' button on frontend
- Fix: Bug in module configuration

## 3.3.3
- Fix: Bug with multycompany compatibility
- Fix: Bug in frontend products tab
- Fix: Translation correction
- New:Optional Help Tab

## 3.3.2
- Fix: Bug with multycompany compatibility

## 3.3.1
- Fix: Warning on frontend login
- New:Catalan translation
- Fix: Bug on Credit Note

## 3.3.0
- New:Graphic design improvements
- New:Integration with 2Rewards
- New:Compatibility with Multientity module
- New:More reports on backend
- New:Customers discount on frontend
- New:POS works with multiprice
- New:Tickets can be classified to Cancelled
- New:Gift ticket
- New:Fullscreen button on frontend
- New:Possibility to print and send by email tickets on frontend
- New:Close cash rights
- New:Ticket and ticket line's notes
- New:Draft ticket can be deleted
- New:Places
- New:Product's price can be changed on frontend
- New:Can see stock of all warehouses on frontend
- New:Possibility to sell or not products without stock

## 3.2.8
- Fix: Bug in terminal statics

## 3.2.7
- Fix: Bug in facsim numeration

## 3.2.6
- Fix: Bug on products with negative price
- Fix: Bug on POS reports
- New:Adapt to new european directive about simplified invoices

## 3.2.5
- Fix: Bug printing close cash
- Fix: Customer creations's bug from frontend
- Fix: Security improvements
- Fix: Save ticket bug
- Fix: Return to backend when pos not's in custom
- Fix: Select employees on frontend
- Fix: Logout terminal bug
- Fix: Pagination closecash bug
- Fix: Include bug

## 3.2.4
- New:Help link
- New:Readme
- Fix: Discount bug
- Fix: Frontend's security

## 3.2.3
- Fix: Not possible to sell under min price.
- Fix: Ticket payment must be a modal window.

## 3.2.2
- Fix: Works if no use custom directory
- Fix: Sales journal works with Dolibarr 3.3
- Fix: Graph compatibility

## 3.2.1
- Fix: Bad link

## 3.2.0
- New:Compatibility with Dolibarr 3.2

## 3.1.1
- Fix: Show subcategories in Frontend
- Fix: Add scrollbars in customers tab in Frontend
- Fix: Draft ticket no payable or printable
