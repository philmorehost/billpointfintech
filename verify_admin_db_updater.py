
import asyncio
from playwright.async_api import async_playwright, expect

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page()

        try:
            # --- Login as Admin ---
            await page.goto("http://localhost:8080/admin/login.php")
            await page.get_by_placeholder("Username").fill("admin")
            await page.get_by_placeholder("Password").fill("password")
            await page.get_by_role("button", name="Login").click()
            await expect(page).to_have_url("http://localhost:8080/admin/index.php")

            # --- Verify Banner and take Screenshot ---
            await expect(page.get_by_text("Database Update Required")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/verification_db_updater.png")

        except Exception as e:
            print(f"An error occurred: {e}")

        finally:
            await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
