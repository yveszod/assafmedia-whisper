import React, { useState } from "react";
import RequestOTP from "./RequestOTP";
import VerifyOTP from "./VerifyOTP";

const Login: React.FC = () => {
  const [screen, setScreen] = useState<1 | 2>(1);
  const [username, setUserName] = useState("");

  return (
    <div>
      {screen === 1 && (
        <>
          <h2>Login Page</h2>
          <RequestOTP setScreen={setScreen} setUserName={setUserName} />
        </>
      )}
      {screen === 2 && (
        <>
          <h2>Enter OTP</h2>
          <VerifyOTP
            username={username}
          />
        </>
      )}
    </div>
  );
};

export default Login;
