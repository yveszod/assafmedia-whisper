import React, { useState } from "react";

type Props = {
  username: string;
};

const VerifyOTP: React.FC<Props> = ({ username }) => {
  const [otp, setOtp] = useState("");
  const redirectUrl = "http://localhost:8080"; // Change this to your desired redirect URL

  const handleOtpSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    try {
      const response = await fetch("/otp/verify.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          otp: otp,
          username: username,
        }),
      });
      const data = await response.json();
      if (data.status === 200) {
        window.location.href = redirectUrl;
      }
    } catch (error) {
      console.error("OTP request failed:", error);
    }
  };
  return (
    <form onSubmit={handleOtpSubmit} autoComplete="off">
      <div>
        <label htmlFor="otp">OTP:</label>
        <input
          type="text"
          id="otp"
          name="otp"
          value={otp}
          onChange={(e) => setOtp(e.target.value)}
          required
        />
      </div>
      <button type="submit">Verify OTP</button>
    </form>
  );
};

export default VerifyOTP;
