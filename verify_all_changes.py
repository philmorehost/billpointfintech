
import asyncio
from playwright.async_api import async_playwright, expect

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page()
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

            # --- Service Pages ---
            await page.goto("http://localhost:8080/user/data.php")
            await expect(page.get_by_text("Buy Data")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/verification_data.png")

            await page.goto("http://localhost:8080/user/cable.php")
            await expect(page.get_by_text("Cable TV")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/verification_cable.png")

            await page.goto("http://localhost:8080/user/electricity.php")
            await expect(page.get_by_text("Electricity Bill")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/verification_electricity.png")

            await page.goto("http://localhost:8080/user/exam.php")
            await expect(page.get_by_text("Exam Pins")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/verification_exam.png")

            # --- Profile Pages ---
            await page.goto("http://localhost:8080/user/edit-information.php")
            await expect(page.get_by_text("Edit Information")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/verification_edit_info.png")

            await page.goto("http://localhost:8080/user/security.php")
            await expect(page.get_by_text("Security Settings")).to_be_visible()
            await page.screenshot(path="/app/tmp_user_files/verification_security.png")

        except Exception as e:
            print(f"An error occurred: {e}")

        finally:
            await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
