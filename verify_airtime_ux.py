
import asyncio
from playwright.async_api import async_playwright, expect

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page()

        try:
            # Navigate to the login page
            await page.goto("http://localhost:8080/user/login.php")

            # Fill in the login form and submit
            await page.get_by_placeholder("Email").fill("testuser@example.com")
            await page.get_by_placeholder("Password").fill("password")
            await page.get_by_role("button", name="Login").click()

            # Wait for navigation to the security pin page
            await expect(page).to_have_url("http://localhost:8080/user/security-pin.php")

            # Fill in the security pin
            await page.get_by_placeholder("PIN").fill("1234")
            await page.get_by_role("button", name="Verify").click()

            # Wait for navigation to the dashboard
            await expect(page).to_have_url("http://localhost:8080/user/dashboard.php")

            # Go to the redesigned airtime page
            await page.goto("http://localhost:8080/user/airtime.php")

            # Interact with the new UI
            await page.get_by_placeholder("Phone Number").fill("08031234567")

            # Wait for the network to be auto-selected
            await expect(page.locator(".network-item[data-network='MTN']")).to_have_class("network-item selected")

            # Click a predefined amount
            await page.get_by_role("button", name="₦200").click()

            # Verify the amount is filled
            await expect(page.locator("#amount")).to_have_value("200")

            # Take a screenshot of the filled form
            await page.screenshot(path="/app/tmp_user_files/airtime_ux_verification.png")

        except Exception as e:
            print(f"An error occurred: {e}")

        finally:
            await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
