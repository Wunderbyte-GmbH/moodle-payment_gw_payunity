@paygw @paygw_payunity @javascript
Feature: PayUnity basic configuration and useage by user
  In order buy shopping_cart items as a user
  I configure PayUnity in background to use company corporative account.

  Background:
    Given the following "users" exist:
      | username | firstname  | lastname    | email                       |
      | user1    | Username1  | Test        | toolgenerator1@example.com  |
      | user2    | Username2  | Test        | toolgenerator2@example.com  |
      | teacher  | Teacher    | Test        | toolgenerator3@example.com  |
      | manager  | Manager    | Test        | toolgenerator4@example.com  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | user2    | C1     | student        |
      | teacher  | C1     | editingteacher |
    And the following "core_payment > payment accounts" exist:
      | name           |
      | Account1       |
    And the following "paygw_payunity > configuration" exist:
      | account  | gateway  | enabled |
      | Account1 | payunity | 1       |
    And the following "local_shopping_cart > plugin setup" exist:
      | account  |
      | Account1 |

  @javascript
  Scenario: PayUnity: user select two items and pay via card using payunity
    Given I log in as "user1"
    And Testitem "1" has been put in shopping cart of user "user1"
    And Testitem "2" has been put in shopping cart of user "user1"
    And I visit "/local/shopping_cart/checkout.php"
    And I wait until the page is ready
    And I should see "Your shopping cart"
    And I should see "my test item 1" in the ".checkoutgrid.checkout #item-local_shopping_cart-main-1" "css_element"
    And I should see "10.00 EUR" in the ".checkoutgrid.checkout #item-local_shopping_cart-main-1 .item-price" "css_element"
    And I should see "my test item 2" in the ".checkoutgrid.checkout #item-local_shopping_cart-main-2" "css_element"
    And I should see "20.30 EUR" in the ".checkoutgrid.checkout #item-local_shopping_cart-main-2 .item-price" "css_element"
    ## Price
    And I should see "30.30 EUR" in the ".sc_price_label" "css_element"
    Then I press "Checkout"
    And I wait until the page is ready
    ##And I wait "1" seconds
    And I should see "PayUnity" in the ".core_payment_gateways_modal" "css_element"
    And I should see "Cost: EUR" in the ".core_payment_fee_breakdown" "css_element"
    And I should see "30.30" in the ".core_payment_fee_breakdown" "css_element"
    And I press "Proceed"
    And I wait until the page is ready
    And I wait "2" seconds
    ## The only way to deal with fields in the ifram is xpath
    And I set the field with xpath "//input[contains(@class, 'wpwl-control-expiry')]" to "05/35"
    And I set the field with xpath "//input[contains(@class, 'wpwl-control-cardHolder')]" to "Behat Test"
    And I set the field with xpath "//iframe[@name='card.number']" to "4111 1111 1111 1111"
    And I set the field with xpath "//iframe[@name='card.cvv']" to "123"
    And I press "Pay now"
    And I wait until the page is ready
    And I should see "Payment successful!"
    And I should see "my test item 1" in the ".payment-success ul.list-group" "css_element"
    And I should see "my test item 2" in the ".payment-success ul.list-group" "css_element"
