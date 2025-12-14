
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

            # Wait for the page to be fully loaded and take a screenshot
            await expect(page.get_by_text("Select Network")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/airtime_redesign_verification.png")

        except Exception as e:
            print(f"An error occurred: {e}")

        finally:
            await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
