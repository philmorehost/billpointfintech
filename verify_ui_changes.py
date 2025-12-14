
import asyncio
from playwright.async_api import async_playwright, expect

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page()
        # Set a mobile viewport for responsive checks
        await page.set_viewport_size({"width": 375, "height": 667})

        try:
            # --- Login ---
            await page.goto("http://localhost:8080/user/login.php")
            await page.get_by_placeholder("Email").fill("testuser@example.com")
            await page.get_by_placeholder("Password").fill("password")
            await page.get_by_role("button", name="Login").click()
            await expect(page).to_have_url("http://localhost:8080/user/security-pin.php")
            await page.get_by_placeholder("PIN").fill("1234")
            await page.get_by_role("button", name="Verify").click()
            await expect(page).to_have_url("http://localhost:8080/user/dashboard.php")

            # --- 1. Dashboard Screenshot ---
            await expect(page.get_by_text("Services")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/verification_dashboard.png")

            # --- 2. Support Page Screenshot ---
            await page.goto("http://localhost:8080/user/support.php")
            await expect(page.get_by_text("Create New Ticket")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/verification_support.png")

            # --- 3. Profile Page Screenshot ---
            await page.goto("http://localhost:8080/user/profile.php")
            await expect(page.get_by_text("My Profile")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/verification_profile.png")

            # --- 4. Bank Transfer Page Screenshot ---
            await page.goto("http://localhost:8080/user/bank-transfer.php")
            await expect(page.get_by_text("Bank Transfer")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/verification_bank_transfer.png")


        except Exception as e:
            print(f"An error occurred: {e}")

        finally:
            await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
