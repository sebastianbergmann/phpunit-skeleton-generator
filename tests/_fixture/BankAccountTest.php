<?php
class BankAccountTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers BankAccount::getBalance
     */
    public function testBalanceIsInitiallyZero()
    {
        $ba = new BankAccount;
        $this->assertEquals(0, $ba->getBalance());

        return $ba;
    }

    /**
     * @covers            BankAccount::withdrawMoney
     * @covers            BankAccount::setBalance
     * @covers            BankAccountException
     * @depends           testBalanceIsInitiallyZero
     * @expectedException BankAccountException
     */
    public function testBalanceCannotBecomeNegative(BankAccount $ba)
    {
        $ba->withdrawMoney(1);
    }

    /**
     * @covers            BankAccount::depositMoney
     * @covers            BankAccount::setBalance
     * @covers            BankAccountException
     * @depends           testBalanceIsInitiallyZero
     * @expectedException BankAccountException
     */
    public function testBalanceCannotBecomeNegative2(BankAccount $ba)
    {
        $ba->depositMoney(-1);
    }

    /**
     * @depends testBalanceIsInitiallyZero
     * @covers  BankAccount::depositMoney
     * @covers  BankAccount::setBalance
     */
    public function testDepositingMoneyWorks(BankAccount $ba)
    {
        $ba->depositMoney(1);
        $this->assertEquals(1, $ba->getBalance());

        return $ba;
    }

    /**
     * @depends testDepositingMoneyWorks
     * @covers  BankAccount::withdrawMoney
     */
    public function testWithdrawingMoneyWorks(BankAccount $ba)
    {
        $ba->withdrawMoney(1);
        $this->assertEquals(0, $ba->getBalance());
    }
}
