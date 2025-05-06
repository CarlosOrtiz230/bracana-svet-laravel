# semgrep-container/test_code.py

def unsafe_eval(user_input):
    # this pattern (use of `eval`) should trigger Semgrep’s builtin S702 rule
    return eval(user_input)

def main():
    cmd = input("Enter math expression: ")
    result = unsafe_eval(cmd)
    print("Result is", result)

if __name__ == "__main__":
    main()

