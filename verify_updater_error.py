
import asyncio
from playwright.async_api import async_playwright, expect

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page()

        try:
            await page.goto("http://localhost:8080/update_database.php")
            await expect(page.get_by_text("Configuration File Missing")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/verification_updater_error.png")

        except Exception as e:
            print(f"An error occurred: {e}")

        finally:
            await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
