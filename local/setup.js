import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import inquirer from "inquirer";
import chalk from "chalk";
import { execSync } from "child_process";

console.clear();

console.log(
  chalk.blue(`

        .
       ":"
     ___:____     |"\\/""|
   ,'        \`.    \\  /
   |  O        \\___/  |
 ~^~^~^~^~^~^~^~^~^~^~^~^~
 ------- ${chalk.yellow("WhalePress")} --------
`)
);

// Function to get the directory name
function getDirname(importMetaUrl) {
  const __filename = fileURLToPath(importMetaUrl);
  return path.dirname(__filename);
}

// Function to load variables from .env-template file
function loadEnvTemplate() {
  const dirname = getDirname(import.meta.url);
  const envFile = fs.readFileSync(
    path.join(dirname, "..", ".env-example"),
    "utf8"
  );
  const envVariables = {};
  envFile.split("\n").forEach((line) => {
    if (line.trim() !== "" && line.indexOf("#") !== 0) {
      const [keyWithValue, comment] = line.split("#");
      const [key, value] = keyWithValue.split("=");
      envVariables[key.trim()] = {
        value: value.trim(),
        description: comment ? comment.trim() : "",
      };
    }
  });
  return envVariables;
}

// Function to prompt user for values using inquirer
async function promptUser(envVariables) {
  const questions = Object.keys(envVariables).map((key) => ({
    type: "input",
    name: key,
    message: envVariables[key].description || `Enter value for ${key}:`,
    default: envVariables[key].value || "",
  }));

  return await inquirer.prompt(questions);
}

// Function to save new .env file with user-provided values
function saveEnv(envVariables) {
  const dirname = getDirname(import.meta.url);
  let envContent = "";
  for (const key in envVariables) {
    envContent += `${key}=${envVariables[key]}\n`;
  }
  fs.writeFileSync(path.join(dirname, "..", ".env"), envContent);
  console.log(chalk.green("New .env file saved successfully!"));
}

// Function to build theme assets
async function buildThemeAssets() {
  const dirname = getDirname(import.meta.url);
  const themeEnvPath = path.join(dirname, "..", ".env");
  const themeEnvExists = fs.existsSync(themeEnvPath);
  if (!themeEnvExists) {
    console.log(
      chalk.red(
        "ERROR: .env file for the theme not found. You need to populate the .env file first."
      )
    );
    return;
  }
  const themeEnvContent = fs.readFileSync(themeEnvPath, "utf8");
  const themeNameMatch = themeEnvContent.match(/THEME_NAME=(.+)/);
  if (!themeNameMatch) {
    console.log(
      chalk.red(
        "ERROR: THEME_NAME variable not found in the .env file. You need to populate the .env file first."
      )
    );
    return;
  }
  const themeName = themeNameMatch[1].trim();
  const themePath = path.join(dirname, "..", "wp-content", "themes", themeName);
  const themePackageJsonPath = path.join(themePath, "package.json");
  if (!fs.existsSync(themePackageJsonPath)) {
    console.log(
      chalk.red(
        `ERROR: package.json not found in the theme directory (${themePath}). Make sure the theme directory is correct or create a package.json file within the theme.`
      )
    );
    return;
  }
  console.log(`Installing npm dependencies for the theme ${themeName}...`);
  execSync("npm install", { stdio: "inherit", cwd: themePath });
  
  console.log(`Building assets for the theme ${themeName}...`);
  try {
    execSync("npm run dev", { stdio: "inherit", cwd: themePath });
    console.log(chalk.green(`Theme assets built successfully using 'npm run dev'!`));
  } catch (error) {
    console.log(
      chalk.yellow(
        `Warning: 'npm run dev' command not available or failed for theme ${themeName}. Skipping theme build.`
      )
    );
    console.log(
      chalk.gray(
        "Make sure your theme has a 'dev' script defined in package.json for development builds."
      )
    );
  }
}

// Function to display border
function displayBorder() {
  console.log(chalk.gray("\n-------------------------------------------\n"));
}

// Main function
async function main() {
  console.log(chalk.black.bgMagenta("Let's get things ready..."));

  // Ask user if they want to build the .env file
  displayBorder();
  const { buildEnv } = await inquirer.prompt([
    {
      type: "confirm",
      name: "buildEnv",
      message: chalk.black.bgWhite("Do you want to build a new .env file?"),
      default: true,
    },
  ]);

  if (buildEnv) {
    console.log(chalk.green("Let's setup your local .env file..."));
    const envVariables = loadEnvTemplate();
    const answers = await promptUser(envVariables);
    saveEnv(answers);
  }

  // Ask user if they want to build Docker containers
  displayBorder();
  const { buildContainers } = await inquirer.prompt([
    {
      type: "confirm",
      name: "buildContainers",
      message: chalk.black.bgWhite(
        "Do you want to build the Docker containers?"
      ),
      default: true,
    },
  ]);

  if (buildContainers) {
    try {
      console.log("Building Docker containers...");
      execSync("docker compose build", { stdio: "inherit" });
      console.log("Docker containers built successfully!");
    } catch (error) {
      console.error("Error building Docker containers:", error);
    }
  }

  //Shall we run the start.js script in this same directory?
  displayBorder();
  const { runStart } = await inquirer.prompt([
    {
      type: "confirm",
      name: "runStart",
      message: chalk.black.bgWhite("Do you want to run the start script?"),
      default: true,
    },
  ]);

  if (runStart) {
    console.log("Running start script...");
    execSync("node start.js", {
      stdio: "inherit",
      cwd: getDirname(import.meta.url),
    });
  }
}

main();
